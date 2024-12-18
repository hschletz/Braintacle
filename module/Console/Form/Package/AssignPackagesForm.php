<?php

namespace Console\Form\Package;

use Laminas\InputFilter\ArrayInput;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Session\Validator\Csrf;
use Laminas\Validator\InArray;
use Model\ClientOrGroup;

/**
 * Assign packages to a client or group.
 */
class AssignPackagesForm
{
    private InputFilter $inputFilter;
    private InArray $packagesInArray;

    public function __construct()
    {
        $this->inputFilter = new InputFilter();

        $csrf = new Input('csrf');
        /** @psalm-suppress InvalidArgument https://github.com/laminas/laminas-session/pull/94 */
        $csrf->setRequired(true)
            ->getValidatorChain()
            ->attach(new Csrf(['timeout' => null]), true);
        $this->inputFilter->add($csrf);

        $this->packagesInArray = new InArray();
        $this->packagesInArray->setStrict(InArray::COMPARE_STRICT);

        $packages = new ArrayInput('packages');
        $packages->getValidatorChain()->attach($this->packagesInArray);
        $this->inputFilter->add($packages);
    }

    /**
     * Assign packages to given target (client or group).
     *
     * @return array<array-key, array<array-key, string|array>>
     */
    public function process(array $formData, ClientOrGroup $target): array
    {
        $this->packagesInArray->setHaystack($target->getAssignablePackages());
        $this->inputFilter->setData($formData);
        if ($this->inputFilter->isValid()) {
            $packages = $this->inputFilter->getValue('packages');
            foreach ($packages as $package) {
                $target->assignPackage($package);
            }
        }

        return $this->inputFilter->getMessages();
    }
}
