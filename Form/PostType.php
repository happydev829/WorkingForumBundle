<?php

namespace Yosimitso\WorkingForumBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PostType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content', TextareaType::class,['translation_domain' => 'YosimitsoWorkingForumBundle','label' => 'forum.content', 'attr' => array('class' => 'wf_textarea_post')])
       
            
            
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yosimitso\WorkingForumBundle\Entity\Post'
        ));
    }

    /**
     * @return string
     */
  /*  public function getName()
    {
        return 'yosimitso_working_forumbundle_post';
    }*/
}