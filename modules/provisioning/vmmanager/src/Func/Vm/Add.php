<?php

namespace IspApi\Func\Vm;

use IspApi\Func\AbstractFunc;

/**
 * Class UserAdd
 */
class Add extends AbstractFunc
{
    protected $func = 'vm.edit';

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

    public function setHostnode(string $hostnode): self {
        $this->additional['hostnode'] = $hostnode;
        return $this;
    }

    public function setUser(string $user): self {
        $this->additional['user'] = $user;
        return $this;
    }

    public function setOsTemplate(string $ostemplate): self {
        $this->additional['os_template'] = $ostemplate;
        return $this;
    }

    public function setPreset(string $preset): self {
        $this->additional['preset'] = $preset;
        return $this;
    }

    public function setIp(string $ip): self {
        $this->additional['ip'] = $ip;
        return $this;
    }

    public function setDomain(string $domain): self {
        $this->additional['domain'] = $domain;
        return $this;
    }

    public function setPassword(string $password): self {
        $this->additional['password'] = $password;
        return $this;
    }
}
