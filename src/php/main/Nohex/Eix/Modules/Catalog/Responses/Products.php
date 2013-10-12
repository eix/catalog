<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

/**
 * Provides means to respond to a contact request.
 */
class _____Products extends \Nohex\Eix\Modules\Catalog\Responses\Html
// If this class name does not end up in an error, the class is not being used
// and can be deleted.
{
    public function issue()
    {
        $type = $this->getRequest()->getParameter('grup');

        // Get the relevant data.
        $products = new \Nohex\Eix\Modules\Catalog\Data\Products;
        $productList = $products->getAll($type);

        foreach ($productList as $product) {
            // Truncate the description if it is too long.
            $description = $product->getDescription();
            if (strlen($description) > 150) {
                $product->setDescription(substr($description, 0, 147) . '...');
            }
        }
        $this->setData('products', $productList);
        $title = _('Products');
        switch ($type) {
            case 'fruita':
                $title = _('Fruit');
                break;
            case 'verdura':
                $title = _('Grocery');
                break;
            case 'exotics':
                $title = _('Exotic');
                break;
            case 'envasats':
                $title = _('Packaged');
                break;
            case 'proximitat':
                $title = _('Proximity');
                break;
        }
        $this->setTitle(_('Products') . ' — ' . _($title));

        // Set the response.
        $this->setTemplateId('products/index');

        parent::issue();
    }

}
