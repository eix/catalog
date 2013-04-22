<?php

namespace Nohex\Eix\Modules\Catalog\Data\Sources;

use Nohex\Eix\Services\Data\Source as DataSource;

class MockProducts implements DataSource
{
    private $products = array(
        'AUB01' => array(
            'id' => 'AUB01',
        ),
    );

    public function create(array $data)
    {
        throw new \LogicException('Not implemented!');
    }

    public function retrieve($id)
    {
        return $this->products[$id];
    }

    public function retrieveAll(array $filter = null, array $fields = null)
    {
        return $this->products;
    }

    public function update($id, array $data)
    {
        throw new \LogicException('Not implemented!');
    }

    public function destroy($id)
    {
        throw new \LogicException('Not implemented!');
    }
}
