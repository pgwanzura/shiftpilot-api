<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contact\CreateContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $contacts = $this->contactService->getContacts($request->all());
        return response()->json([
            'success' => true,
            'data' => $contacts,
            'message' => 'Contacts retrieved successfully'
        ]);
    }

    public function store(CreateContactRequest $request): JsonResponse
    {
        $contact = $this->contactService->createContact($request->validated());
        return response()->json([
            'success' => true,
            'data' => new ContactResource($contact),
            'message' => 'Contact created successfully'
        ]);
    }

    public function show(Contact $contact): JsonResponse
    {
        $contact->load(['employer', 'user', 'shiftApprovals']);
        return response()->json([
            'success' => true,
            'data' => new ContactResource($contact),
            'message' => 'Contact retrieved successfully'
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $contact = $this->contactService->updateContact($contact, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new ContactResource($contact->load(['employer', 'user', 'shiftApprovals'])),
            'message' => 'Contact updated successfully'
        ]);
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $this->contactService->deleteContact($contact);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Contact deleted successfully'
        ]);
    }
}
