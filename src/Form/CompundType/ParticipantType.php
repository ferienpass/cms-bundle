<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

namespace Ferienpass\CmsBundle\Form\CompundType;

use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepositoryInterface;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ParticipantType extends AbstractType
{
    public function __construct(private readonly Security $security, private readonly EditionRepository $editions, #[Autowire(param: 'ferienpass.model.participant.class')] private readonly string $participantEntityClass, private readonly ParticipantRepositoryInterface $participants)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Vorname',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nachname',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('dateOfBirth', BirthdayType::class, [
                'label' => 'Geburtsdatum',
                'widget' => 'single_text',
                'input' => 'datetime',
                'placeholder' => 'tt.mm.jjjj',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;

        $user = $this->security->getUser();
        if (!$user) {
            $builder->add('email', EmailType::class, [
                'label' => 'E-Mail-Adresse',
                'attr' => [
                    'placeholder' => 'email@beispiel.de',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ]);
        }

        if (!$user) {
            $builder->add('mobile', TelType::class, [
                'label' => 'Handynummer',
                'required' => false,
                'constraints' => [
                    new PhoneNumber(type: PhoneNumber::MOBILE, defaultRegion: 'DE'),
                ],
                'attr' => [
                    'placeholder' => '0172-0000000',
                ],
            ]);
        }

        $editionsWithCode = $options['edition'] ? [$options['edition']] : $this->editions->findWithActiveAccessCodeStrategies();
        $builder->add('accessCodes', AccessCodesType::class, [
            'editions' => $editionsWithCode,
            'by_reference' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $user = $this->security->getUser();

        $resolver->setDefined('edition');
        $resolver->addAllowedTypes('edition', [Edition::class, 'null']);

        $resolver->setDefaults([
            'edition' => null,
            'label_format' => 'apply.%name%',
            'translation_domain' => 'cms',
            'data_class' => $this->participantEntityClass,
            'empty_data' => function (FormInterface $form) use ($user) {
                $participant = $this->participants->createNew();
                $participant->setUser($user);

                return $participant;
            },
        ]);
    }

    private function isEditable(string $field): bool
    {
        return match ($field) {
            'firstname', 'lastname', 'dateOfBirth' => false,
            default => true
        };
    }
}
