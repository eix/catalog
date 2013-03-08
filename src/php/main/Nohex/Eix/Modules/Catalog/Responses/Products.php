<?php

namespace Nohex\Eix\Modules\Catalog\Responses;

/**
 * Provides means to respond to a contact request.
 */
class Products extends \Nohex\Eix\Modules\Catalog\Responses\Html
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

        $title = 'Productes';
        switch ($type) {
            case 'fruita':
                $title = _('Fruita');
                break;
            case 'verdura':
                $title = _('Verdura');
                break;
            case 'exotics':
                $title = _('Exòtics');
                break;
            case 'envasats':
                $title = _('Envasats');
                break;
            case 'proximitat':
                $title = _('Proximitat');
                break;
        }
        $this->setTitle(_('Productes') . ' — ' . _($title));

        // Set the response.
        $this->setTemplateId('products/index');

        parent::issue();
    }

}
