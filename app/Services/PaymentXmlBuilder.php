<?php

namespace App\Services;

use App\DTOs\PaymentRequestData;
use DOMDocument;
use DOMElement;

class PaymentXmlBuilder
{
    public function build(PaymentRequestData $data): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('PaymentRequest');
        $dom->appendChild($root);

        $this->addElement($dom, $root, 'Reference', $data->reference);
        $this->addElement($dom, $root, 'Date', $data->date);
        $this->addElement($dom, $root, 'Amount', $data->amount);
        $this->addElement($dom, $root, 'Currency', $data->currency);
        $this->addElement($dom, $root, 'SenderAccount', $data->senderAccount);
        $this->addElement($dom, $root, 'ReceiverBankCode', $data->receiverBankCode);
        $this->addElement($dom, $root, 'ReceiverAccount', $data->receiverAccount);
        $this->addElement($dom, $root, 'BeneficiaryName', $data->beneficiaryName);

        if (! empty($data->notes)) {
            $notesElement = $dom->createElement('Notes');
            $root->appendChild($notesElement);
            foreach ($data->notes as $note) {
                $this->addElement($dom, $notesElement, 'Note', $note);
            }
        }

        if ($data->paymentType !== 99) {
            $this->addElement($dom, $root, 'PaymentType', (string) $data->paymentType);
        }

        if ($data->chargeDetails !== 'SHA') {
            $this->addElement($dom, $root, 'ChargeDetails', $data->chargeDetails);
        }

        return $dom->saveXML();
    }

    private function addElement(DOMDocument $dom, DOMElement $parent, string $tag, string $value): void
    {
        $element = $dom->createElement($tag, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
        $parent->appendChild($element);
    }
}
