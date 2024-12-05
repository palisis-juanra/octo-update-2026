<?php

namespace App\Transformers;

class ContactTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($contact): array
    {
        return [
            'fullName' => $contact->getFullName(),
        ];
    }

    protected function fullTransform($contact): array
    {
        return [
            'fullName' => $contact->getFullName(),
            'firstName' => $contact->getFirstName(),
            'lastName' => $contact->getLastName(),
            'emailAddress' => $contact->getEmailAddress(),
            'phoneNumber' => $contact->getPhoneNumber(),
            'locales' => $contact->getLocales(),
            'postalCode' => $contact->getPostalCode(),
            'country' => $contact->getCountry(),
            'notes' => $contact->getNotes()
        ];
    }
}