<?php
namespace Scheb\TwoFactorBundle\Security\TwoFactor\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Response;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedFilter;

class RequestListener
{

    /**
     * @var \Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedFilter $trustedFilter
     */
    private $trustedFilter;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     */
    private $securityContext;

    /**
     * @var array $supportedTokens
     */
    private $supportedTokens;

    /**
     * Construct a listener for login events
     *
     * @param \Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedFilter $registry
     * @param \Symfony\Component\Security\Core\SecurityContextInterface       $securityContext
     * @param array                                                           $supportedTokens
     */
    public function __construct(TrustedFilter $trustedFilter, SecurityContextInterface $securityContext, array $supportedTokens)
    {
        $this->trustedFilter = $trustedFilter;
        $this->securityContext = $securityContext;
        $this->supportedTokens = $supportedTokens;
    }

    /**
     * Listen for request events
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onCoreRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $token = $this->securityContext->getToken();
        if (!$this->isTokenSupported($token)) {
            return;
        }

        // Forward to two factor provider
        // Providers can create a response object
        $response = $this->trustedFilter->requestAuthenticationCode($request, $token);

        // Set the response (if there is one)
        if ($response instanceof Response) {
            $event->setResponse($response);
        }
    }

    /**
     * Check if the token class is supported
     *
     * @param  mixed   $token
     * @return boolean
     */
    private function isTokenSupported($token)
    {
        $class = get_class($token);

        return in_array($class, $this->supportedTokens);
    }
}
