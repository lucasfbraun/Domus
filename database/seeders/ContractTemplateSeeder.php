<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use App\Support\ContractTemplateVariables;
use App\Support\StandardLeaseContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Publica o modelo padrão de contrato de locação residencial, já com
     * todas as variáveis do catálogo. Idempotente: seguro rodar em qualquer
     * ambiente (dev, staging, produção) sem duplicar registros.
     */
    public function run(): void
    {
        ContractTemplate::query()->updateOrCreate(
            ['name' => StandardLeaseContractTemplate::NAME],
            ['content' => ContractTemplateVariables::sanitizeHtml(StandardLeaseContractTemplate::content())],
        );
    }
}
