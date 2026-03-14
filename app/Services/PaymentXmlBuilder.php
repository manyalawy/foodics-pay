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

        $root = $dom->createElement('PaymentRequestMessage');
        $dom->appendChild($root);

        $transferInfo = $dom->createElement('TransferInfo');
        $root->appendChild($transferInfo);
        $this->addElement($dom, $transferInfo, 'Reference', $data->reference);
        $this->addElement($dom, $transferInfo, 'Date', $data->date);
        $this->addElement($dom, $transferInfo, 'Amount', $data->amount);
        $this->addElement($dom, $transferInfo, 'Currency', $data->currency);

        $senderInfo = $dom->createElement('SenderInfo');
        $root->appendChild($senderInfo);
        $this->addElement($dom, $senderInfo, 'AccountNumber', $data->senderAccount);

        $receiverInfo = $dom->createElement('ReceiverInfo');
        $root->appendChild($receiverInfo);
        $this->addElement($dom, $receiverInfo, 'BankCode', $data->receiverBankCode);
        $this->addElement($dom, $receiverInfo, 'AccountNumber', $data->receiverAccount);
        $this->addElement($dom, $receiverInfo, 'BeneficiaryName', $data->beneficiaryName);

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
