<?php


namespace App\Serializer;


use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class UserAttributeNormalizer implements ContextAwareNormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    const USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED = 'USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
      $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     * @param array $context option that normalizers have access to
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
       if (isset($context[self::USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED])) {
           return false;
       }

       return $data instanceof User;
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     * @param mixed $object              Object to normalize
     * @param string $format             Format the normalization result will be encoded as
     * @param array $context             Context options for the normalizer
     * @return array|string|int|float|bool
     * @throws InvalidArgumentException  Occurs when the object given is not called in an expected context
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($this->isUserHimself($object)) {
            $context['groups'][] = 'get-owner';
        }

        // Now continue with serialization
        return $this->passON($object, $format, $context);
    }

    private function isUserHimself($object)
    {
        return $object->getUsername() === $this->tokenStorage->getToken()->getUsername();
    }

    private function passOn($object, $format, $context) {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new \LogicException (
            sprintf('Cannot normalize object "%s" because the injected serializer is not a normalizer',
                $object
                )
             );
        }
        $context[self::USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED] = true;

        return $this->serializer->normalize($object, $format, $context);
    }

}