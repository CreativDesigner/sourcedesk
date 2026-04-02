<?php

namespace IspApi\Func\Vm;

use IspApi\Func\AbstractFunc;

/**
 * Class UserDelete
 */
class Suspend extends AbstractFunc
{
    protected $func = 'vm.edit';

    /**
     * Edit constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->additional['status'] = '0';
    }
}
