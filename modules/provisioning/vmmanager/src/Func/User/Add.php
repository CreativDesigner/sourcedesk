<?php

namespace IspApi\Func\User;

use IspApi\Func\AbstractFunc;

/**
 * Class UserAdd
 */
class Add extends AbstractFunc
{
    protected $func = 'user.edit';

    /**
     * Add constructor.
     */
    public function __construct()
    {
        $this->additional['sok'] = 'ok';
        parent::__construct();
    }

    /**
     * @param string $name
     *
     * @return Add
     */
    public function setName(string $name): self
    {
        $this->additional['name'] = $name;
        return $this;
    }

    public function setLevel(string $level): self {
        $this->additional['level'] = $level;
        return $this;
    }

    /**
     * @param string $password
     *
     * @return Add
     */
    public function setPassword(string $password): self
    {
        $this->additional['passwd'] = $password;
        $this->additional['confirm'] = $password;
        return $this;
    }
}
