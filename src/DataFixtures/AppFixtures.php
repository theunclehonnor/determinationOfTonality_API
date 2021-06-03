<?php

namespace App\DataFixtures;

use App\Entity\Model;
use App\Entity\Resource;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        // создание пользователей системы
        $this->userLoad($manager);

        // создание листа моделей
        $this->modelLoad($manager);

        // создание интернет ресурса
        $this->resourceLoad($manager);

        $manager->flush();
    }

    public function userLoad($manager)
    {
//        /// Обычного пользователя
//        $user = new User();
//        $user->setEmail('user@yandex.ru');
//        $user->setSurname('Артуров');
//        $user->setName('Артур');
//        $user->setPatronymic('Артурович');
//        $user->setPassword($this->passwordEncoder->encodePassword(
//            $user,
//            'user123'
//        ));
//        $user->setRoles(['ROLE_USER']);
//        $manager->persist($user);

        // Админ пользователь
        $user = new User();
        $user->setEmail('admin@yandex.ru');
        $user->setSurname('Админович');
        $user->setName('Админ');
        $user->setPatronymic('Админов');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            'admin123'
        ));
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);
    }

    public function modelLoad($manager)
    {
        // BagOfWords
        $listModels = new Model();
        $listModels->setName('BagOfWords');
        $listModels->setClassificator('MultinomialNB');
        $listModels->setDataSet('womenShop');
        $listModels->setPath('/bagOfWord_method/predict');
        $manager->persist($listModels);

        $listModels = new Model();
        $listModels->setName('BagOfWords');
        $listModels->setClassificator('RandomForest');
        $listModels->setDataSet('womenShop');
        $listModels->setPath('/bagOfWord_method/predict');
        $manager->persist($listModels);

        $listModels = new Model();
        $listModels->setName('BagOfWords');
        $listModels->setClassificator('MultinomialNB');
        $listModels->setDataSet('twitter');
        $listModels->setPath('/bagOfWord_method/predict');
        $manager->persist($listModels);

        $listModels = new Model();
        $listModels->setName('BagOfWords');
        $listModels->setClassificator('RandomForest');
        $listModels->setDataSet('twitter');
        $listModels->setPath('/bagOfWord_method/predict');
        $manager->persist($listModels);

        // Word2Vec
        $listModels = new Model();
        $listModels->setName('Word2Vec');
        $listModels->setClassificator('MultinomialNB');
        $listModels->setDataSet('womenShop');
        $listModels->setPath('/word2Vec_method/predict');
        $manager->persist($listModels);

        $listModels = new Model();
        $listModels->setName('Word2Vec');
        $listModels->setClassificator('RandomForest');
        $listModels->setDataSet('womenShop');
        $listModels->setPath('/word2Vec_method/predict');
        $manager->persist($listModels);

        $listModels = new Model();
        $listModels->setName('Word2Vec');
        $listModels->setClassificator('MultinomialNB');
        $listModels->setDataSet('twitter');
        $listModels->setPath('/word2Vec_method/predict');
        $manager->persist($listModels);

        $listModels = new Model();
        $listModels->setName('Word2Vec');
        $listModels->setClassificator('RandomForest');
        $listModels->setDataSet('twitter');
        $listModels->setPath('/word2Vec_method/predict');
        $manager->persist($listModels);
    }

    public function resourceLoad($manager)
    {
        // М.видео
        $resource = new Resource();
        $resource->setName('М.видео');
        $resource->setLink('https://www.mvideo.ru/');
        $manager->persist($resource);

        // Продокторов | врачи
        $resource = new Resource();
        $resource->setName('Продокторов | врачи');
        $resource->setLink('https://prodoctorov.ru/');
        $manager->persist($resource);
    }
}
