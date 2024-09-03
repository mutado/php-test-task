<?php

namespace Operations\Notification\Builders;

use Operations\Notification\Contractor;
use Operations\Notification\DTO\TemplateDataDTO;
use Operations\Notification\Employee;

/**
 * TemplateDataBuilder class
 *
 * Pattern: Builder
 * Purpose: Used to build the template data required for sending notifications.
 * This makes the process of building complex data structures more readable and less error-prone.
 */
class TemplateDataBuilder
{
    private array $data = [];

    public function setComplaint(int $id, string $number): self
    {
        $this->data['COMPLAINT_ID'] = $id;
        $this->data['COMPLAINT_NUMBER'] = $number;
        return $this;
    }

    public function setCreator(Employee $creator): self
    {
        $this->data['CREATOR_ID'] = $creator->id;
        $this->data['CREATOR_NAME'] = $creator->getFullName();
        return $this;
    }

    public function setExpert(Employee $expert): self
    {
        $this->data['EXPERT_ID'] = $expert->id;
        $this->data['EXPERT_NAME'] = $expert->getFullName();
        return $this;
    }

    public function setClient(Contractor $client): self
    {
        $this->data['CLIENT_ID'] = $client->id;
        $this->data['CLIENT_NAME'] = $client->getFullName() ?: $client->name;
        return $this;
    }

    public function setConsumption(int $id, string $number): self
    {
        $this->data['CONSUMPTION_ID'] = $id;
        $this->data['CONSUMPTION_NUMBER'] = $number;
        return $this;
    }

    public function setAgreement(string $number): self
    {
        $this->data['AGREEMENT_NUMBER'] = $number;
        return $this;
    }

    public function setDate(string $date): self
    {
        $this->data['DATE'] = $date;
        return $this;
    }

    public function setDifferences(string $differences): self
    {
        $this->data['DIFFERENCES'] = $differences;
        return $this;
    }

    public function build(): TemplateDataDTO
    {
        foreach ($this->data as $key => $value) {
            if (empty($value)) {
                throw new Exception("Template Data ({$key}) is empty!", 500);
            }
        }
        return new TemplateDataDTO(
            $this->data['COMPLAINT_ID'],
            $this->data['COMPLAINT_NUMBER'],
            $this->data['CREATOR_ID'],
            $this->data['CREATOR_NAME'],
            $this->data['EXPERT_ID'],
            $this->data['EXPERT_NAME'],
            $this->data['CLIENT_ID'],
            $this->data['CLIENT_NAME'],
            $this->data['CONSUMPTION_ID'],
            $this->data['CONSUMPTION_NUMBER'],
            $this->data['AGREEMENT_NUMBER'],
            $this->data['DATE'],
            $this->data['DIFFERENCES']
        );
    }
}