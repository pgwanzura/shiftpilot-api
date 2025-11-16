<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ValidateSystemSchema extends Command
{
    protected $signature = 'system:audit 
                            {--models : Validate Eloquent models}
                            {--migrations : Validate migrations}
                            {--fix : Attempt to fix issues}
                            {--debug : Show debug information}';

    protected $description = 'Validate system schema against models and migrations';

    protected array $config;
    protected array $errors = [];
    protected array $warnings = [];
    protected array $models = [];

    public function handle()
    {
        // Load the configuration
        $this->config = config('schema');

        if (!$this->config) {
            $this->error('Configuration not found!');
            return 1;
        }

        $this->info('🔍 Validating ShiftPilot System...');

        if ($this->option('debug')) {
            $this->debugMigrations();
        }

        // Always run basic validations
        $this->validateTables();
        $this->validateColumns();
        $this->validateResourceStructure();

        // Conditional validations
        if ($this->option('models')) {
            $this->loadModels();
            $this->validateModels();
        }

        if ($this->option('migrations')) {
            $this->validateMigrations();
            $this->validateMigrationContent();
        }

        $this->displayResults();

        if ($this->option('fix')) {
            $this->attemptFixes();
        }

        return count($this->errors) > 0 ? 1 : 0;
    }

    protected function validateTables(): void
    {
        $this->info('📊 Validating database tables...');

        $expectedTables = array_keys($this->config['resources']);
        $existingTables = $this->getExistingTables();

        foreach ($expectedTables as $table) {
            $tableName = $this->config['resources'][$table]['table'] ?? $table;

            if (!in_array($tableName, $existingTables)) {
                $this->error("Missing table: {$tableName}");
                $this->errors[] = "Table missing: {$tableName}";
            }
        }
    }

    protected function validateColumns(): void
    {
        $this->info('📝 Validating table columns...');

        foreach ($this->config['resources'] as $resourceName => $resource) {
            $tableName = $resource['table'] ?? $resourceName;

            if (!Schema::hasTable($tableName)) {
                $this->warnings[] = "Cannot validate columns for missing table: {$tableName}";
                continue;
            }

            $existingColumns = $this->getTableColumns($tableName);
            $expectedColumns = array_keys($resource['fields']);

            foreach ($expectedColumns as $column) {
                if (!$existingColumns->contains($column)) {
                    // Check if this column exists in migration files
                    if ($this->columnExistsInMigrations($tableName, $column)) {
                        $this->warnings[] = "Column pending migration: {$tableName}.{$column} (exists in migration but not in database)";
                    } else {
                        $this->error("Missing column: {$tableName}.{$column}");
                        $this->errors[] = "Column missing: {$tableName}.{$column}";
                    }
                } else {
                    // Column exists, validate its properties
                    $this->validateColumnProperties($tableName, $column, $resource['fields'][$column]);
                }
            }
        }
    }

    protected function columnExistsInMigrations(string $tableName, string $column): bool
    {
        $migrationFiles = File::allFiles(database_path('migrations'));

        foreach ($migrationFiles as $file) {
            $content = File::get($file->getPathname());

            // Check if this migration handles our table
            if (!$this->migrationHandlesTable($content, $tableName)) {
                continue;
            }

            // Check for explicit column definitions
            if ($this->hasExplicitColumnDefinition($content, $column)) {
                return true;
            }

            // Check for schema builder methods that create multiple columns
            if ($this->hasSchemaBuilderMethod($content, $column)) {
                return true;
            }
        }

        return false;
    }

    protected function migrationHandlesTable(string $content, string $tableName): bool
    {
        return preg_match("/Schema::(create|table)\\(\\s*['\"]{$tableName}['\"]/", $content);
    }

    protected function hasExplicitColumnDefinition(string $content, string $column): bool
    {
        $patterns = [
            "/\\\$table->[a-zA-Z]+\(\s*['\"]{$column}['\"][^)]*\)/",
            "/\\\$table->[a-zA-Z]+\(\s*['\"]{$column}['\"]\s*\)/",
            "/['\"]{$column}['\"]\s*=>/",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    // ADDED: Method to detect schema builder methods like timestamps(), softDeletes(), etc.
    protected function hasSchemaBuilderMethod(string $content, string $column): bool
    {
        // Map columns to their schema builder methods
        $schemaMethods = [
            'created_at' => ['timestamps', 'nullableTimestamps', 'timestampsTz'],
            'updated_at' => ['timestamps', 'nullableTimestamps', 'timestampsTz'],
            'deleted_at' => ['softDeletes', 'softDeletesTz'],
            'id' => ['id', 'bigIncrements', 'increments'],
            'remember_token' => ['rememberToken'],
        ];

        if (!isset($schemaMethods[$column])) {
            return false;
        }

        foreach ($schemaMethods[$column] as $method) {
            // Simple string matching is more reliable than regex for this case
            if (str_contains($content, "\$table->{$method}()")) {
                return true;
            }
            // Also check with spaces
            if (str_contains($content, "\$table->{$method} (")) {
                return true;
            }
        }

        return false;
    }

    protected function validateColumnProperties(string $tableName, string $column, array $config): void
    {
        $currentType = $this->getColumnType($tableName, $column);
        $expectedType = $this->mapFieldTypeToDB($config['type']);

        if ($currentType !== $expectedType) {
            $this->warnings[] = "Column type mismatch: {$tableName}.{$column} (current: {$currentType}, expected: {$expectedType})";
        }

        // Check nullable
        $isNullable = $this->isColumnNullable($tableName, $column);
        $shouldBeNullable = $config['nullable'] ?? false;

        if ($isNullable !== $shouldBeNullable) {
            $this->warnings[] = "Nullable mismatch: {$tableName}.{$column} (current: " . ($isNullable ? 'NULL' : 'NOT NULL') . ", expected: " . ($shouldBeNullable ? 'NULL' : 'NOT NULL') . ")";
        }
    }

    protected function validateResourceStructure(): void
    {
        $this->info('🏗️  Validating resource structure...');

        foreach ($this->config['resources'] as $resourceName => $resource) {
            if (!isset($resource['validation']) || empty($resource['validation'])) {
                $this->warnings[] = "Resource {$resourceName} missing validation rules";
            }

            if (!isset($resource['relationships']) || empty($resource['relationships'])) {
                $this->warnings[] = "Resource {$resourceName} missing relationships definition";
            }

            if (!isset($resource['indexes']) || empty($resource['indexes'])) {
                $this->warnings[] = "Resource {$resourceName} missing indexes definition";
            }
        }
    }

    protected function validateMigrations(): void
    {
        $this->info('🔄 Validating migrations existence...');

        $migrationFiles = File::allFiles(database_path('migrations'));

        // Get expected table names from config
        $expectedTables = [];
        foreach ($this->config['resources'] as $resourceName => $resource) {
            $tableName = $resource['table'] ?? $resourceName;
            $expectedTables[$tableName] = $resourceName;
        }

        $tablesInMigrations = $this->findTablesInMigrations($migrationFiles);

        if ($this->option('debug')) {
            $this->info("Found " . count($tablesInMigrations) . " tables referenced in migrations:");
            foreach ($tablesInMigrations as $table) {
                $this->line("  - {$table}");
            }
        }

        foreach ($expectedTables as $tableName => $resourceName) {
            if (!in_array($tableName, $tablesInMigrations)) {
                $this->warnings[] = "No migration found for table: {$tableName} (resource: {$resourceName})";
            }
        }
    }

    protected function findTablesInMigrations($migrationFiles): array
    {
        $tablesInMigrations = [];

        foreach ($migrationFiles as $file) {
            $content = File::get($file->getPathname());

            // Look for Schema::create calls with different quote styles
            if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                $tablesInMigrations = array_merge($tablesInMigrations, $matches[1]);
            }

            // Look for Schema::table calls
            if (preg_match_all('/Schema::table\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                $tablesInMigrations = array_merge($tablesInMigrations, $matches[1]);
            }

            // Look for DB::table calls
            if (preg_match_all('/DB::table\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                $tablesInMigrations = array_merge($tablesInMigrations, $matches[1]);
            }

            // Look for table names in comments or strings that match our expected tables
            $expectedTableNames = array_keys($this->getExpectedTablesFromConfig());
            foreach ($expectedTableNames as $table) {
                if (str_contains($content, $table) && $this->isLikelyTableReference($content, $table)) {
                    $tablesInMigrations[] = $table;
                }
            }
        }

        return array_unique($tablesInMigrations);
    }

    protected function getExpectedTablesFromConfig(): array
    {
        $expectedTables = [];
        foreach ($this->config['resources'] as $resourceName => $resource) {
            $tableName = $resource['table'] ?? $resourceName;
            $expectedTables[$tableName] = $resourceName;
        }
        return $expectedTables;
    }

    protected function isLikelyTableReference(string $content, string $table): bool
    {
        // Avoid matching common words or variable names
        $falsePositives = ['table', 'migration', 'create', 'update', 'delete'];

        if (in_array($table, $falsePositives)) {
            return false;
        }

        // Check if table appears in a likely migration context
        $patterns = [
            "/['\"]{$table}['\"]/",
            "/table.*['\"]{$table}['\"]/",
            "/{$table}.*table/",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    protected function validateMigrationContent(): void
    {
        $this->info('📋 Validating migration content...');

        $migrationFiles = File::allFiles(database_path('migrations'));

        foreach ($migrationFiles as $file) {
            $content = File::get($file->getPathname());

            // Check each table in this migration
            if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $tableName) {
                    $this->validateMigrationTableContent($tableName, $content, $file->getFilename());
                }
            }
        }
    }

    protected function validateMigrationTableContent(string $tableName, string $content, string $filename): void
    {
        // Find the resource for this table
        $resource = null;
        foreach ($this->config['resources'] as $res) {
            if (($res['table'] ?? '') === $tableName) {
                $resource = $res;
                break;
            }
        }

        if (!$resource || !isset($resource['fields'])) {
            return;
        }

        // Check if each expected column exists in migration
        foreach ($resource['fields'] as $column => $config) {
            if (!$this->isColumnDefinedInMigration($content, $column)) {
                $this->warnings[] = "Migration {$filename} missing column: {$tableName}.{$column}";
            }
        }
    }

    // ADDED: Comprehensive method to check if column is defined in migration
    protected function isColumnDefinedInMigration(string $content, string $column): bool
    {
        // Check for explicit column definitions
        if ($this->hasExplicitColumnDefinition($content, $column)) {
            return true;
        }

        // Check for schema builder methods
        if ($this->hasSchemaBuilderMethod($content, $column)) {
            return true;
        }

        // Additional check: look for the column in any table method call
        $patterns = [
            "/->[a-zA-Z]+\(\s*['\"]{$column}['\"]/", // Any method with this column name
            "/\\\$table->[a-zA-Z]+.*{$column}/",     // Any table method mentioning this column
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    protected function debugMigrations(): void
    {
        $this->info('🐛 DEBUG: Scanning migration files...');

        $migrationFiles = File::allFiles(database_path('migrations'));
        $expectedTables = $this->getExpectedTablesFromConfig();

        $this->info("Expected tables from config: " . implode(', ', array_keys($expectedTables)));

        foreach ($migrationFiles as $file) {
            $this->line("\nChecking: " . $file->getFilename());
            $content = File::get($file->getPathname());

            // Find all potential table references
            if (preg_match_all('/[\'"]([a-z_]+)[\'"]/', $content, $matches)) {
                $potentialTables = array_unique($matches[1]);
                $foundTables = [];

                foreach ($potentialTables as $table) {
                    // Filter to only show tables that match our expected tables
                    if (isset($expectedTables[$table]) && strlen($table) > 2) {
                        $foundTables[] = $table;
                    }
                }

                if (!empty($foundTables)) {
                    $this->line("  Found tables: " . implode(', ', $foundTables));
                }
            }

            // Also show schema builder methods used
            if (preg_match_all('/\\\$table->([a-zA-Z]+)\(\s*\)/', $content, $matches)) {
                $methods = array_unique($matches[1]);
                if (!empty($methods)) {
                    $this->line("  Schema methods: " . implode(', ', $methods));

                    // Debug specific columns
                    $testColumns = ['created_at', 'updated_at', 'id'];
                    foreach ($testColumns as $column) {
                        $hasMethod = $this->hasSchemaBuilderMethod($content, $column);
                        $this->line("  Column '{$column}' found via schema method: " . ($hasMethod ? 'YES' : 'NO'));
                    }
                }
            }
        }
    }

    protected function loadModels(): void
    {
        $this->info('📦 Loading Eloquent models...');

        $modelFiles = File::allFiles(app_path('Models'));

        foreach ($modelFiles as $file) {
            $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
                    $this->models[] = $className;
                }
            }
        }
    }

    protected function validateModels(): void
    {
        $this->info('🎯 Validating Eloquent models...');

        foreach ($this->models as $modelClass) {
            try {
                $model = new $modelClass;
                $table = $model->getTable();

                // Check if table exists in config
                $found = false;
                foreach ($this->config['resources'] as $resource) {
                    $configTable = $resource['table'] ?? null;
                    if ($configTable === $table) {
                        $found = true;
                        $this->validateModelRelationships($modelClass, $resource);
                        break;
                    }
                }

                if (!$found) {
                    $this->warnings[] = "Model {$modelClass} uses table not in config: {$table}";
                }
            } catch (\Exception $e) {
                $this->warnings[] = "Error validating model {$modelClass}: {$e->getMessage()}";
            }
        }
    }

    protected function validateModelRelationships(string $modelClass, array $resource): void
    {
        if (!isset($resource['relationships'])) {
            return;
        }

        $reflection = new ReflectionClass($modelClass);

        foreach ($resource['relationships'] as $relationship) {
            $expectedMethod = $this->getRelationshipMethodName($relationship);

            if (!$reflection->hasMethod($expectedMethod)) {
                $this->warnings[] = "Model {$modelClass} missing relationship method: {$expectedMethod}()";
            }
        }
    }

    protected function getRelationshipMethodName(array $relationship): string
    {
        $type = $relationship['type'];
        $related = $relationship['related'];

        switch ($type) {
            case 'belongsTo':
            case 'hasOne':
                return Str::camel(Str::singular($related));
            case 'hasMany':
            case 'morphMany':
                return Str::camel(Str::plural($related));
            case 'morphTo':
                return Str::camel($relationship['name'] ?? 'entity');
            default:
                return Str::camel($related);
        }
    }

    protected function attemptFixes(): void
    {
        $this->info('🔧 Attempting to fix issues...');

        foreach ($this->errors as $error) {
            if (str_starts_with($error, 'Missing table:')) {
                $table = str_replace('Missing table: ', '', $error);
                $this->line("Would create migration for table: {$table}");
            } elseif (str_starts_with($error, 'Missing column:')) {
                $this->line("Would create migration to add column: {$error}");
            }
        }

        foreach ($this->warnings as $warning) {
            if (str_contains($warning, 'pending migration')) {
                $this->line("Would run: php artisan migrate");
                break;
            }
        }
    }

    protected function displayResults(): void
    {
        $this->line('');

        if (count($this->errors) > 0) {
            $this->error('❌ Errors: ' . count($this->errors));
            foreach ($this->errors as $error) {
                $this->line("  - {$error}");
            }
            $this->line('');
        }

        if (count($this->warnings) > 0) {
            $this->warn('⚠️  Warnings: ' . count($this->warnings));
            foreach ($this->warnings as $warning) {
                $this->line("  - {$warning}");
            }
            $this->line('');
        }

        if (count($this->errors) === 0 && count($this->warnings) === 0) {
            $this->info('✅ All validations passed!');
        } else {
            $this->info('📊 Summary: ' . count($this->errors) . ' errors, ' . count($this->warnings) . ' warnings');

            if (count(array_filter($this->warnings, fn($w) => str_contains($w, 'pending migration'))) > 0) {
                $this->line('💡 Run: php artisan migrate');
            }
        }
    }

    // Helper methods
    protected function getExistingTables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(function ($table) {
                return array_values((array)$table)[0];
            })
            ->toArray();
    }

    protected function getTableColumns(string $tableName): Collection
    {
        return collect(Schema::getColumnListing($tableName));
    }

    protected function getColumnType(string $tableName, string $column): string
    {
        try {
            $columnInfo = DB::selectOne("
                SELECT DATA_TYPE as type 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ? AND COLUMN_NAME = ?
            ", [$tableName, $column]);

            return $columnInfo ? $columnInfo->type : 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    protected function isColumnNullable(string $tableName, string $column): bool
    {
        try {
            $columnInfo = DB::selectOne("
                SELECT IS_NULLABLE as nullable 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ? AND COLUMN_NAME = ?
            ", [$tableName, $column]);

            return $columnInfo && $columnInfo->nullable === 'YES';
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function mapFieldTypeToDB(string $fieldType): string
    {
        $mapping = [
            'increments' => 'int',
            'bigIncrements' => 'bigint',
            'string' => 'varchar',
            'text' => 'text',
            'integer' => 'int',
            'bigInteger' => 'bigint',
            'decimal' => 'decimal',
            'boolean' => 'tinyint',
            'date' => 'date',
            'timestamp' => 'timestamp',
            'json' => 'json',
            'foreign' => 'bigint',
        ];
        return $mapping[$fieldType] ?? $fieldType;
    }
}
