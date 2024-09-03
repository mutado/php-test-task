<?php

namespace Operations\Notification\Validators;

use Operations\Notification\DTO\ValidationResultDTO;

class ReturnOperationValidator
{
    public function validateData(array $data): ValidationResultDTO
    {
        $errors = [];
        $validatedData = [];

        $requiredFields = ['resellerId', 'notificationType', 'clientId', 'creatorId', 'expertId', 'complaintId', 'complaintNumber', 'consumptionId', 'consumptionNumber', 'agreementNumber', 'date'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Empty {$field}";
            } else {
                $validatedData[$field] = $data[$field];
            }
        }

        if (!empty($validatedData)) {
            $validatedData['resellerId'] = (int)$validatedData['resellerId'];
            $validatedData['notificationType'] = (int)$validatedData['notificationType'];
        }

        return new ValidationResultDTO(empty($errors), $errors, $validatedData);
    }
}