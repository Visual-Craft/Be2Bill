<?php

namespace Payum\Be2Bill\Request\Api;

use Payum\Core\Request\Generic;

class RecurringPayment extends Generic
{
    /**
     * @var string
     */
    private $cardType;

    /**
     * @var string|null
     */
    private $alias;

    /**
     * @var string|null
     */
    private $aliasMode;

    /**
     * @var string|null
     */
    private $fullName;

    /**
     * @param mixed $model
     * @param $alias
     * @param $aliasMode
     * @param $fullName
     * @param $cardType
     */
    public function __construct($model, $alias, $aliasMode, $fullName = null, $cardType = null)
    {
        parent::__construct($model);
        $this->cardType = $cardType;
        $this->alias = $alias;
        $this->aliasMode = $aliasMode;
        $this->fullName = $fullName;
    }

    /**
     * @return mixed
     */
    public function getCardType()
    {
        return $this->cardType;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string|null
     */
    public function getAliasMode()
    {
        return $this->aliasMode;
    }

    /**
     * @return string|null
     */
    public function getFullName()
    {
        return $this->fullName;
    }
}
