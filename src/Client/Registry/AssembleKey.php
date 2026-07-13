<?php

namespace Braintacle\Client\Registry;

use Formotron\PreProcessor;
use InvalidArgumentException;
use Override;

/**
 * Concatenate raw registry definition columns to a full registry path.
 *
 * The input array must contain the following keys:
 * - regtree (int) value from regconfig.regtree, converted to HKEY_*
 *   representation
 * - regkey (string) value from regconfig.regkey
 * - value_name (string) value from regconfig.regvalue, renamed for
 *   disambiguation with registry.regvalue
 *
 * The concatenated path is stored under the "path" key in the output array. The
 * original input keys mentioned above are removed.
 */
class AssembleKey implements PreProcessor
{
    private const RootKey = 'regtree';
    private const SubKeys = 'regkey';
    private const ValueName = 'value_name';
    private const Path = 'path';

    #[Override]
    public function process(array $formData): array
    {
        $rootKey = $formData[self::RootKey] ?? throw new InvalidArgumentException('Missing ' . self::RootKey);
        $subKeys = $formData[self::SubKeys] ?? throw new InvalidArgumentException('Missing ' . self::SubKeys);
        $valueName = $formData[self::ValueName] ?? throw new InvalidArgumentException('Missing ' . self::ValueName);

        $formData[self::Path] = RootKey::from($rootKey)->name . '\\' . $subKeys . '\\' . $valueName;

        unset($formData[self::RootKey]);
        unset($formData[self::SubKeys]);
        unset($formData[self::ValueName]);

        return $formData;
    }
}
