<?php

namespace ColinODell\VarPackagistSearch;

class ResultCollection
{
    private $collection = [];

    private $filename;

    public function __construct($filename = 'results.json')
    {
        $this->filename = __DIR__.'/../tmp/' . $filename;
    }

    public function addResult($i, Package $package)
    {
        $data = [
            'package' => $package->getName(),
            'version' => $package->getVersion(),
        ];

        if ($package->hasError()) {
            $data['error'] = $package->getError();
        } else {
            $data['stats'] = [
                'public' => $package->getPublicCount(),
                'var' => $package->getVarCount(),
                'varUsages' => $package->getVarUsages(),
            ];
        }

        $this->collection[$i] = $data;

        $this->save();
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->collection);
    }

    private function save()
    {
        file_put_contents($this->filename, json_encode($this->collection));
    }
}
