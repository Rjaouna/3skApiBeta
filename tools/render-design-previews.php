<?php

declare(strict_types=1);

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderLine;
use App\Entity\User;
use App\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

require dirname(__DIR__).'/vendor/autoload.php';

class DesignPreviewKernel extends Kernel
{
    public function getCacheDir(): string
    {
        return dirname(__DIR__).'/tmp/design-audit/preview-cache';
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->setAlias('design.twig', 'twig')->setPublic(true);
        $container->setAlias('design.tokens', 'security.token_storage')->setPublic(true);
        $container->setAlias('design.encore', 'webpack_encore.entrypoint_lookup_collection')->setPublic(true);
        $container->setAlias('design.tags', 'webpack_encore.tag_renderer')->setPublic(true);
    }
}

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new DesignPreviewKernel($_SERVER['APP_ENV'] ?? 'dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$request = Request::create('http://127.0.0.1:8765/');
$request->setSession(new Session(new MockArraySessionStorage()));
$container->get('request_stack')->push($request);
$container->get('router')->getContext()->fromRequest($request);
$twig = $container->get('design.twig');
$render = static function (string $template, array $parameters) use ($container, $twig): string {
    $container->get('design.encore')->getEntrypointLookup()->reset();
    $container->get('design.tags')->reset();
    return $twig->render($template, $parameters);
};
$directory = dirname(__DIR__).'/tmp/design-audit/previews';
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}

// Preview entities remain in memory: this tool never persists or flushes data.
$order = (new CustomerOrder())
    ->setOrderNumber('3SK-APERCU-DESIGN')
    ->setCompanyName('Client de verification')
    ->setCustomerEmail('verification@example.test')
    ->setCustomerCode('APERCU')
    ->setAddress1('Adresse de verification')
    ->setCity('Casablanca')
    ->setSubtotalTtc('300.00')
    ->setTotalTtc('300.00');
(new ReflectionProperty(CustomerOrder::class, 'id'))->setValue($order, 999999);
foreach ([['REF-001', 'PIECE CARROSSERIE DE VERIFICATION', 2], ['REF-002', 'ACCESSOIRE AUTOMOBILE DE VERIFICATION', 1]] as [$reference, $name, $quantity]) {
    $line = (new CustomerOrderLine())->setArticleCode($reference)->setDesignation($name)->setQuantity($quantity)->setUnitPriceTtc('100.00')->setLineTotalTtc((string) (100 * $quantity));
    $order->addLine($line);
}

$previews = [];
foreach (['admin' => 'admin@3skpartsauto.com', 'client' => 'client@3skpartsauto.com'] as $role => $email) {
    $user = $container->get('doctrine')->getRepository(User::class)->findOneBy(['email' => $email]);
    if (!$user) {
        throw new RuntimeException('Local fixture account missing.');
    }
    $container->get('design.tokens')->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    $order->setCustomer($user);
    $request->attributes->set('_route', $role === 'admin' ? 'admin_orders_show' : 'order_show');
    $templates = $role === 'admin' ? ['admin-order' => 'admin/order/show.html.twig', 'order-print' => 'admin/order/print.html.twig'] : ['client-order' => 'order/show.html.twig'];
    foreach ($templates as $name => $template) {
        $html = $render($template, ['order' => $order, 'nextStatus' => CustomerOrder::STATUS_PREPARING, 'statusLabels' => CustomerOrder::getStatusLabels()]);
        $html = str_replace('<head>', '<head><base href="http://127.0.0.1:8765/">', $html);
        file_put_contents($directory.'/'.$name.'.html', $html);
        $previews[] = $name;
    }
}
$container->get('design.tokens')->setToken(null);
foreach ([403, 404, 500] as $status) {
    $html = $render('bundles/TwigBundle/Exception/error'.$status.'.html.twig', ['status_code' => $status]);
    file_put_contents($directory.'/error-'.$status.'.html', str_replace('<head>', '<head><base href="http://127.0.0.1:8765/">', $html));
    $previews[] = 'error-'.$status;
}
echo json_encode(['rendered' => $previews, 'databaseWrites' => false], JSON_PRETTY_PRINT).PHP_EOL;
$kernel->shutdown();
