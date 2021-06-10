<?php

namespace App\Controller\Admin;

use App\Entity\ObjectInQuestion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class ObjectInQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ObjectInQuestion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Рассматриваемый объект')
            ->setEntityLabelInPlural('Рассматриваемые объекты')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name', 'Название'),
            AssociationField::new('resource', 'Интернет-ресурс'),
            AssociationField::new('model', 'Модель'),
            UrlField::new('link', 'Ссылка'),
            UrlField::new('fileReviews', 'Файл с отзывами'),
            AvatarField::new('image', 'Картинка'),
        ];
    }
}
