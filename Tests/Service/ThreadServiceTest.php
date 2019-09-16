<?php

namespace Yosimitso\WorkingForumBundle\Tests\Service;

//use Symfony\Bundle\FrameworkBundle\Test\TestCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Yosimitso\WorkingForumBundle\Entity\Post;
use Yosimitso\WorkingForumBundle\Entity\PostReport;
use Yosimitso\WorkingForumBundle\Entity\Subforum;
use Yosimitso\WorkingForumBundle\Entity\Thread;
use Yosimitso\WorkingForumBundle\Entity\User;
use Yosimitso\WorkingForumBundle\Form\PostType;
use Yosimitso\WorkingForumBundle\Form\ThreadType;
use Yosimitso\WorkingForumBundle\Security\Authorization;
use Yosimitso\WorkingForumBundle\Service\ThreadService;
use Yosimitso\WorkingForumBundle\Tests\Mock\EntityManagerMock;
use Knp\Component\Pager\Paginator;
use Yosimitso\WorkingForumBundle\Util\FileUploader;
use Symfony\Component\Form\FormFactory;

/**
 * Class ThreadControllerTest
 *
 * @package Yosimitso\WorkingForumBundle\Tests\Controller
 */
class ThreadServiceTest extends TestCase
{

    public function getTestedClass($em = null, $user = null, $authorization = null)
    {
        if (is_null($user)) {
            $user = $this->createMock(User::class);
            $user->setUsername = 'toto';
        }

        if (is_null($authorization)) {
            $authorization = $this->createMock(Authorization::class);
        }


        $testedClass = new ThreadService(
            0,
            $this->createMock(Paginator::class),
            10,
            $this->createMock(RequestStack::class),
            $em,
            $user,
            $this->createMock(FileUploader::class),
            $authorization,
            ['allow_moderator_delete_thread' => false],
            $this->getFormFactory()
        );

        return $testedClass;
    }

    private function getFormFactory()
    {
        $formFactory = $this->createMock(FormFactory::class);
        $formView = $this->createMock(FormView::class);
        $classFormFactory = new class($formView)
        {
            public function __construct($formView)
            {
                $this->formView = $formView;
            }

            function createView()
            {

                return $this->formView;
            }
        };
        $formFactory->method('create')->willReturn($classFormFactory);

        return $formFactory;
    }

    public function testPin()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

//        $entity = new class extends TestCase  {
//            public function findOneBySlug($a) {
//                return new Thread;
//            }
//        };
//
//        $em->method('getRepository')->willReturn($entity);

        $testedClass = $this->getTestedClass($em);

        $thread = new Thread;
        $this->assertTrue($testedClass->pin($thread));
        $this->assertTrue($em->getFlushedEntities()[0]->getPin());
    }

    public function testResolved()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();
        $testedClass = $this->getTestedClass($em);

        $thread = new Thread;

        $this->assertTrue($testedClass->resolve($thread));
        $this->assertTrue($em->getFlushedEntities()[0]->getResolved());
    }

    public function testLocked()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();
        $testedClass = $this->getTestedClass($em);

        $thread = new Thread;

        $this->assertTrue($testedClass->lock($thread));
        $this->assertTrue($em->getFlushedEntities()[0]->getLocked());
    }

    public function testReport()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $testedClass = $this->getTestedClass($em);

        $post = new Post;
        $this->assertTrue($testedClass->report($post));
        $this->assertTrue($em->getFlushedEntities()[0] instanceof PostReport);
    }


    public function testMove()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $testedClass = $this->getTestedClass($em);

        $thread = new Thread;
        $thread->setNbReplies(5);

        $currentSubforum = new Subforum;
        $currentSubforum->setName('former');
        $currentSubforum->setNbThread(20);
        $currentSubforum->setNbPost(50);

        $targetSubforum = new Subforum;
        $targetSubforum->setName('new');
        $targetSubforum->setNbThread(20);
        $targetSubforum->setNbPost(50);

        $this->assertTrue($testedClass->move($thread, $currentSubforum, $targetSubforum));
        $this->assertTrue($em->getFlushedEntities()[0] instanceof Thread);
        $this->assertTrue($em->getFlushedEntities()[1] instanceof Subforum);
        $this->assertTrue($em->getFlushedEntities()[2] instanceof Subforum);


        $this->assertEquals('new', $em->getFlushedEntities()[0]->getSubforum()->getName()); // THREAD MOVE TO THE RIGHT SUBFORUM
        $this->assertEquals(19, $em->getFlushedEntities()[1]->getNbThread()); // STATISTICS ARE UPDATED
        $this->assertEquals(21, $em->getFlushedEntities()[2]->getNbThread()); // STATISTICS ARE UPDATED

        $this->assertEquals(45, $em->getFlushedEntities()[1]->getNbPost()); // STATISTICS ARE UPDATED
        $this->assertEquals(55, $em->getFlushedEntities()[2]->getNbPost()); // STATISTICS ARE UPDATED
    }

    public function testDelete()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $testedClass = $this->getTestedClass($em);

        $thread = new Thread;
        $thread->setNbReplies(20);

        $subforum = new Subforum;
        $subforum->setNbThread(20);
        $subforum->setNbPost(50);

        $this->assertTrue($testedClass->delete($thread, $subforum));

        $this->assertEquals(19, $em->getFlushedEntity(Subforum::class)->getNbThread());
        $this->assertEquals(30, $em->getFlushedEntity(Subforum::class)->getNbPost());
//        $this->assertTrue($em->getRemovedEntities()[0] instanceof Thread);
//        $this->assertTrue($em->getFlushedEntities()[1] instanceof Thread);
    }

    public function testCreate()
    {
        /**
         * @var EntityManagerMock
         */
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $user = $this->createMock(User::class);
        $user->setUsername = 'toto';

        $testedClass = $this->getTestedClass($em, $user);

        $form = $this->getMockBuilder(ThreadType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $class = new class
        {
            function getPost()
            {
                $secondClass = new class
                {
                    function getFilesUploaded()
                    {
                        return [];
                    }
                };

                return [0 => $secondClass];

            }
        };

        $form->method('getData')->willReturn($class);
        $thread = new Thread;

        $subforum = new Subforum;
        $subforum->setNbThread(20);
        $subforum->setNbPost(50);

        $post = new Post;
        $post->setContent('test');

        $this->assertTrue($testedClass->create($form, $post, $thread, $subforum));

        $user = $em->getFlushedEntity(get_class($user));
        $subforum = $em->getFlushedEntity(Subforum::class);
        $thread = $em->getFlushedEntity(Thread::class);
        $post = $em->getFlushedEntity(Post::class);

        $this->assertEquals(21, $subforum->getNbThread());
        $this->assertEquals(1, $thread->getNbReplies());
        $this->assertEquals('test', $post->getContent());
//        $this->assertEquals(1, $user->getNbPost());
    }

    public function testCreateWithFiles()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $user = $this->createMock(User::class);
        $user->setUsername = 'toto';

        $testedClass = $this->getTestedClass($em, $user);

        $form = $this->getMockBuilder(ThreadType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $class = new class
        {
            function getPost()
            {
                $secondClass = new class
                {
                    function getFilesUploaded()
                    {
                        $file = new UploadedFile(__DIR__.'/../Mock/file_test.jpg', "file_test.jpg");
                        return [$file];
                    }
                };

                return [0 => $secondClass];

            }
        };

        $form->method('getData')->willReturn($class);
        $thread = new Thread;

        $subforum = new Subforum;
        $subforum->setNbThread(20);
        $subforum->setNbPost(50);

        $post = new Post;
        $post->setContent('test');

        $this->assertTrue($testedClass->create($form, $post, $thread, $subforum));

        $user = $em->getFlushedEntity(get_class($user));
        $subforum = $em->getFlushedEntity(Subforum::class);
        $thread = $em->getFlushedEntity(Thread::class);
        $post = $em->getFlushedEntity(Post::class);

        $this->assertEquals(21, $subforum->getNbThread());
        $this->assertEquals(1, $thread->getNbReplies());
//        $this->assertEquals(1, $user->getNbPost());

    }

    public function testPost()
    {
        $em = $this->getMockBuilder(EntityManagerMock::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $user = $this->createMock(User::class);
        $user->setUsername = 'toto';

        $testedClass = $this->getTestedClass($em, $user);

        $subforum = new Subforum;
        $subforum->setNbThread(20);
        $subforum->setNbPost(50);

        $thread = new Thread;
        $thread->setNbReplies(20);

        $post = new Post;
        $post->setContent('test');

        $form = $this->getMockBuilder(PostType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $class = new class
        {
            function getFilesUploaded()
            {
                return [];
            }
        };

        $form->method('getData')->willReturn($class);

        $this->assertTrue($testedClass->post($subforum, $thread, $post, $user, $form));

        $user = $em->getFlushedEntity(get_class($user));
        $subforum = $em->getFlushedEntity(Subforum::class);
        $thread = $em->getFlushedEntity(Thread::class);
        $post = $em->getFlushedEntity(Post::class);

        $this->assertEquals(20, $subforum->getNbThread());
        $this->assertEquals(51, $subforum->getNbPost());
        $this->assertEquals(21, $thread->getNbReplies());
        $this->assertEquals('test', $post->getContent());

    }

    public function testGetAvailableActionsClassicUser()
    {
        $testedClass = $this->getTestedClass();

        // CLASSIC USER, NOT THREAD'S AUTHOR
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $author = $this->createMock(User::class);
        $author->method('getId')->willReturn(2);
        $thread = new Thread;
        $thread->setAuthor($author);

        $result = $testedClass->getAvailableActions($user, $thread, false, true);

        $this->assertFalse($result['setResolved']);
        $this->assertTrue($result['quote']);
        $this->assertTrue($result['report']);
        $this->assertTrue($result['post']);
        $this->assertTrue($result['subscribe']);
        $this->assertFalse($result['moveThread']);
        $this->assertFalse($result['allowModeratorDeleteThread']);

        // USER IS THE THREAD'S AUTHOR
        $thread->setAuthor($user);

        $result = $testedClass->getAvailableActions($user, $thread, false, true);
        $this->assertTrue($result['setResolved']); // THREAD'S AUTHOR CAN "RESOLVE" HIS THREAD


    }

    public function testGetAvailableActionsAnonymousUser()
    {
        $testedClass = $this->getTestedClass();

        // ANONYMOUS USER
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(null);
        $thread = new Thread;

        $result = $testedClass->getAvailableActions($user, $thread, false, true);
        $this->assertFalse($result['setResolved']);
        $this->assertFalse($result['quote']);
        $this->assertFalse($result['report']);
        $this->assertFalse($result['post']);
        $this->assertFalse($result['subscribe']);
        $this->assertFalse($result['moveThread']);
        $this->assertFalse($result['allowModeratorDeleteThread']);
    }

    public function testGetAvailableActionsModerator()
    {
        $authorization = $this->createMock(Authorization::class);
        $authorization->method('hasModeratorAuthorization')->willReturn(true);

        $testedClass = $this->getTestedClass(null, null, $authorization);


        // MODERATOR
        $user = $this->createMock(User::class);
        $thread = new Thread;

        $result = $testedClass->getAvailableActions($user, $thread, false, true);
        $this->assertFalse($result['setResolved']);
        $this->assertFalse($result['quote']);
        $this->assertFalse($result['report']);
        $this->assertFalse($result['post']);
        $this->assertFalse($result['subscribe']);
        $this->assertTrue($result['moveThread'] instanceof FormView);
        $this->assertFalse($result['allowModeratorDeleteThread']);
    }

    public function testGetAvailableActionsAdmin()
    {
        $authorization = $this->createMock(Authorization::class);
        $authorization->method('hasModeratorAuthorization')->willReturn(true);

        $testedClass = $this->getTestedClass(null, null, $authorization);


        // MODERATOR
        $user = $this->createMock(User::class);
        $thread = new Thread;

        $result = $testedClass->getAvailableActions($user, $thread, false, true);
        $this->assertFalse($result['setResolved']);
        $this->assertFalse($result['quote']);
        $this->assertFalse($result['report']);
        $this->assertFalse($result['post']);
        $this->assertFalse($result['subscribe']);
        $this->assertTrue($result['moveThread'] instanceof FormView);
        $this->assertFalse($result['allowModeratorDeleteThread']);
    }

}
