<?php

namespace Illuminate\Session;

use BadMethodCallException;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class SymfonySessionDecorator implements SessionInterface
{
    /**
     * The underlying Laravel session store.
     *
     * @var \Illuminate\Session\Store
     */
    protected $store;

    /**
     * Create a new session decorator.
     *
     * @param  \Illuminate\Session\Store  $store
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        return $this->store->start();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->store->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id)
    {
        $this->store->setId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->store->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name)
    {
        $this->store->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(int $lifetime = null): bool
    {
        $this->store->invalidate();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(bool $destroy = false, int $lifetime = null): bool
    {
        $this->store->migrate($destroy);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $this->store->save();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return $this->store->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->store->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, mixed $value)
    {
        $this->store->put($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->store->all();
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes)
    {
        $this->store->replace($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): mixed
    {
        return $this->store->remove($name);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->store->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->store->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag)
    {
        throw new BadMethodCallException('Method not implemented by Laravel.');
    }

    /**
     * {@inheritdoc}
     */
    public function getBag(string $name): SessionBagInterface
    {
        throw new BadMethodCallException('Method not implemented by Laravel.');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataBag(): MetadataBag
    {
        throw new BadMethodCallException('Method not implemented by Laravel.');
    }
}
