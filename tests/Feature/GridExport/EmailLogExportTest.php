<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Test\Factory\DocumentEmailFactory;
use OpenDxp\Test\Factory\EmailLogFactory;
use OpenDxp\Test\Factory\UserFactory;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->document = DocumentEmailFactory::createOne();
});

it('exports the columns of the email log without the bodies', function () {
    $file = exportThroughAdmin($this->admin, 'email-logs', parameters: ['documentId' => $this->document->getId()]);

    expect($file)
        ->header()
        ->toBe([
            'ID',
            'Document ID',
            'Date sent',
            'From',
            'Reply To',
            'To',
            'Cc',
            'Bcc',
            'Subject',
            'Parameters',
            'Error',
        ]);
});

it('exports the email log of a document, the newest email first', function () {
    EmailLogFactory::new()
        ->forDocument($this->document)
        ->sentAt(1700000000)
        ->create(['subject' => 'Older']);
    EmailLogFactory::new()
        ->forDocument($this->document)
        ->sentAt(1800000000)
        ->create(['subject' => 'Newer']);
    EmailLogFactory::new()
        ->forDocument(DocumentEmailFactory::createOne())
        ->create();

    $file = exportThroughAdmin($this->admin, 'email-logs', parameters: ['documentId' => $this->document->getId()]);

    expect($file)
        ->column('Subject')
        ->toBe(['Newer', 'Older']);
});

it('exports the date an email was sent in the timezone of the user', function () {
    EmailLogFactory::new()
        ->forDocument($this->document)
        ->sentAt(1700000000)
        ->create();

    $file = exportThroughAdmin($this->admin, 'email-logs', parameters: ['documentId' => $this->document->getId()]);

    expect($file)
        ->column('Date sent')
        ->toBe(['2023-11-14 22:13:20']);
});

it('exports only the selected emails', function () {
    $selected = EmailLogFactory::new()
        ->forDocument($this->document)
        ->create(['subject' => 'Selected']);
    EmailLogFactory::new()
        ->forDocument($this->document)
        ->create();

    $file = exportThroughAdmin($this->admin, 'email-logs', selectedIds: [$selected->getId()]);

    expect($file)
        ->column('Subject')
        ->toBe(['Selected']);
});
