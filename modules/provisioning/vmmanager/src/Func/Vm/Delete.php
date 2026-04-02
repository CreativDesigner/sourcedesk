<?php

namespace IspApi\Func\Vm;

use IspApi\Func\AbstractFunc;

/**
 * Class UserDelete
 */
class Delete extends AbstractFunc
{
    protected $func = 'vm.delete';

    /**
     * Edit constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function force() {
        $this->additional['force'] = 'on';
    }
}
