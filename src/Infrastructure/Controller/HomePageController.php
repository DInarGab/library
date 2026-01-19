<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookController extends AbstractController
{
    #[Route('/')]
    public function home(

    ): Response
    {
        $number = random_int(0, 10);

        return new Response(
            '<html><body>Lucky number: ' . $number . '</body></html>'
        );
    }
}
