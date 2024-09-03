<?php

namespace Operations\Notification\DTO;

class TemplateDataDTO
{
    public function __construct(
        public int $complaintId,
        public string $complaintNumber,
        public int $creatorId,
        public string $creatorName,
        public int $expertId,
        public string $expertName,
        public int $clientId,
        public string $clientName,
        public int $consumptionId,
        public string $consumptionNumber,
        public string $agreementNumber,
        public string $date,
        public string $differences
    ) {}
}