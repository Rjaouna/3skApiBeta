<?php

namespace App\DataFixtures;

use App\Entity\HeroSlide;
use App\Repository\HeroSlideRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Installe le slider de référence de l'accueil sur une installation vierge.
 *
 * Les fichiers indiqués ci-dessous doivent être déployés dans public/img/slides/.
 * Cette fixture ne remplace jamais une configuration que l'administrateur a
 * déjà créée : si au moins un slide existe, elle ne fait rien.
 */
final class HeroSlideFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * L'ordre est porté par createdAt car le repository affiche les slides du
     * plus récent au plus ancien (hors slides explicitement mis en avant).
     *
     * @var list<array{
     *     image: string,
     *     title: ?string,
     *     subtitle: ?string,
     *     primaryLabel: ?string,
     *     primaryUrl: ?string,
     *     secondaryLabel: ?string,
     *     secondaryUrl: ?string,
     *     createdAt: string
     * }>
     */
    private const SLIDES = [
        [
            'image' => 'slide-00-69d2b4b750b1b.jpg',
            // Le texte promotionnel est déjà inclus dans le visuel Delphi.
            'title' => null,
            'subtitle' => null,
            'primaryLabel' => null,
            'primaryUrl' => null,
            'secondaryLabel' => null,
            'secondaryUrl' => null,
            'createdAt' => '2026-10-14 08:00:00',
        ],
        [
            'image' => '1772441585-69ef168ddacab.png',
            // Le texte promotionnel est déjà inclus dans le visuel Dolz.
            'title' => null,
            'subtitle' => null,
            'primaryLabel' => null,
            'primaryUrl' => null,
            'secondaryLabel' => null,
            'secondaryUrl' => null,
            'createdAt' => '2026-10-15 08:00:00',
        ],
        [
            'image' => '080920251757318688-69ef174b1e570-6a246a350e402.webp',
            'title' => '3SK Parts Auto',
            'subtitle' => 'Votre partenaire en pièces et accessoires automobiles de qualité, au Maroc.',
            'primaryLabel' => 'Voir nos produits',
            'primaryUrl' => '/articles',
            'secondaryLabel' => 'Nos marques',
            'secondaryUrl' => '/#marques',
            'createdAt' => '2026-10-16 08:00:00',
        ],
    ];

    public function __construct(private readonly HeroSlideRepository $heroSlides)
    {
    }

    public function load(ObjectManager $manager): void
    {
        if ($this->heroSlides->count([]) > 0) {
            return;
        }

        foreach (self::SLIDES as $definition) {
            $mediaPath = dirname(__DIR__, 2) . '/public/img/slides/' . $definition['image'];
            if (!is_file($mediaPath)) {
                throw new \RuntimeException(sprintf(
                    'Le média du slider par défaut est introuvable : %s. Transférez public/img/slides avant de charger la fixture.',
                    $definition['image']
                ));
            }

            $slide = (new HeroSlide())
                ->setImage($definition['image'])
                ->setTitle($definition['title'])
                ->setSubtitle($definition['subtitle'])
                ->setPrimaryLabel($definition['primaryLabel'])
                ->setPrimaryUrl($definition['primaryUrl'])
                ->setSecondaryLabel($definition['secondaryLabel'])
                ->setSecondaryUrl($definition['secondaryUrl'])
                ->setEnabled(true)
                ->setCreatedAt(new \DateTimeImmutable($definition['createdAt']));

            $manager->persist($slide);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['homepage', 'hero-slider'];
    }
}
