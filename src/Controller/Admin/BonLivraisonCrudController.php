<?php

namespace App\Controller\Admin;

use App\Entity\BonLivraison;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BonLivraisonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BonLivraison::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
        AssociationField::new('bon_com'),
        //IntegerEditorField::new('bon_com')->setLabel('Votre Label Personnalisé'),
        // AssociationField::new('bon_com')
        // ->setFormTypeOption('autocomplete', 'native')
        // ->setLabel('Votre Label Personnalisé'),
        ];
    }
    
}
