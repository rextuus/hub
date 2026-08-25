<?php

namespace App\Tool\BetAI\Form;

use App\Tool\BetAI\Entity\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AssignTeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', TeamAutocompleteField::class)
            ->add('assignHome', SubmitType::class, ['label' => 'Als Heim', 'attr' => ['class' => 'btn btn-primary']])
            ->add('assignAway', SubmitType::class, ['label' => 'Als Gast', 'attr' => ['class' => 'btn btn-secondary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // We are not binding to a single entity directly here
        ]);
    }
}
