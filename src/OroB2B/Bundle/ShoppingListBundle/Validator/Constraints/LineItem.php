<?php

namespace OroB2B\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LineItem extends Constraint
{
    public $message = 'Line Item with the same product and unit already exists';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return 'orob2b_shopping_list_line_item_validator';
    }
}
