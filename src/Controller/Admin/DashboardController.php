<?php

namespace App\Controller\Admin;

use App\Entity\Model;
use App\Entity\ObjectInQuestion;
use App\Entity\Report;
use App\Entity\Resource;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        $routeBuilder = $this->get(CrudUrlGenerator::class)->build();
        $url = $routeBuilder->setController(UserCrudController::class)->generateUrl();
        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Административная панель Determination Of Tonality API');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Отчеты', 'fas fa-file', Report::class);
        yield MenuItem::linkToCrud('Рассматриваемые объекты', 'fas fa-eye', ObjectInQuestion::class);
        yield MenuItem::linkToCrud('Веб-ресурсы', 'fas fa-globe', Resource::class);
        yield MenuItem::linkToCrud('Модели', 'fas fa-object-ungroup', Model::class);
    }
}
