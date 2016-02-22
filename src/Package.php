<?php

namespace ColinODell\VarPackagistSearch;

class Package
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $installationPath;

    /**
     * @var int
     */
    private $publicCount = 0;

    /**
     * @var int
     */
    private $varCount = 0;

    /**
     * @var array
     */
    private $varUsages;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @param string $packageName
     */
    public function __construct($packageName)
    {
        $this->name = $packageName;
        $this->installationPath = sys_get_temp_dir().'/'.md5($packageName);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getInstallationPath()
    {
        return $this->installationPath;
    }

    /**
     * @return int
     */
    public function getPublicCount()
    {
        return $this->publicCount;
    }

    /**
     * @param int $publicCount
     */
    public function setPublicCount($publicCount)
    {
        $this->publicCount = $publicCount;
    }

    /**
     * @return int
     */
    public function getVarCount()
    {
        return $this->varCount;
    }

    /**
     * @param int $varCount
     */
    public function setVarCount($varCount)
    {
        $this->varCount = $varCount;
    }

    /**
     * @return array
     */
    public function getVarUsages()
    {
        return $this->varUsages;
    }

    /**
     * @param array $varUsages
     */
    public function setVarUsages($varUsages)
    {
        $this->varUsages = $varUsages;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error !== null;
    }
}
