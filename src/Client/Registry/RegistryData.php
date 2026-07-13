<?php

namespace Braintacle\Client\Registry;

use Braintacle\Dom\DomMapper;
use Formotron\Attribute\PreProcess;
use Override;

/**
 * An inventoried registry value.
 */
#[PreProcess(AssembleKey::class)]
final class RegistryData implements DomMapper
{
    /**
     * Definition name.
     */
    public string $name;

    /**
     * Full path, taken from definition.
     */
    public string $path;

    public string $data;

    #[Override]
    public function exportToDom(): array
    {
        return [
            'NAME' => $this->name,
            'REGVALUE' => $this->data,
        ];
    }
}
