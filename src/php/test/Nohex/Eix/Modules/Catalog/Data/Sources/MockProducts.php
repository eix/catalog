<?php

namespace Nohex\Eix\Modules\Catalog\Data\Sources;

use Nohex\Eix\Services\Data\Source as DataSource;

class MockProducts implements DataSource
{
    public function create(array $data)
    {
        throw new \LogicException('Not implemented!');
    }

    public function retrieve($id)
    {
        throw new \LogicException('Not implemented!');
    }

    public function retrieveAll(array $filter = null, array $fields = null)
    {
        return array(
            array(
                'id' => 'AUB01',
            ),
        );
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
