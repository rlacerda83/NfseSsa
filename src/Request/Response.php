<?php

namespace Rlacerda83\NfseSsa\Request;


class Response
{
    /**
     * @var bool
     */
    private $status;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string|null
     */
    private $xmlContent;

    /**
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param Error $error
     */
    public function addError(Error $error)
    {
        $this->errors[] = $error;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

   /**
     * @return string|null
     */
    public function getXmlContent(): ? string 
    {
        return $this->xmlContent;
    }

   /**
     * @param string|null $xmlContent
     */
    public function setXmlContent(?string $xmlContent): void 
    {
        $this->xmlContent = $xmlContent;
    }
}
