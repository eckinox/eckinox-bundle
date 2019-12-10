<?php

namespace Eckinox\Library\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Eckinox\Library\Symfony\Doctrine\LocaleFilter;

trait translatableEntity {
    /**
     * @ORM\Column(type="string", length=6)
     */
    protected $locale;

    /**
     * This ORM relation is defined dynamically in the TranslationsRelation event subscriber
     */
    protected $translations;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $translated;

    protected $translationCreationType = 'automatic';
    protected $translationDeletionType = 'automatic';


    public function setLocale($locale) {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale() {
        return $this->locale;
    }

    public function setTranslationCreationType($translationCreationType) {
        $this->translationCreationType = $translationCreationType;

        return $this;
    }

    public function getTranslationCreationType() {
        return strtolower(trim($this->translationCreationType ?: 'automatic'));
    }

    public function setTranslationDeletionType($translationDeletionType) {
        $this->translationDeletionType = $translationDeletionType;

        return $this;
    }

    public function getTranslationDeletionType() {
        return strtolower(trim($this->translationDeletionType ?: 'automatic'));
    }

    public function setTranslated($translated) {
        $this->translated = $translated;

        return $this;
    }

    public function getTranslated() {
        return $this->translated;
    }

    public function isTranslated() {
        return $this->getTranslated();
    }

    public function isTranslatedIn($locale) {
        if ($this->getLocale() == $locale) {
            return $this->isTranslated();;
        }

        foreach ($this->getTranslations() as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation->isTranslated();
            }
        }

        return false;
    }

    public function setTranslations(Collection $translations) {
        $this->translations = $translations;

        return $this;
    }

    public function getTranslations(): Collection {
        $reenableFilter = LocaleFilter::isEnabled();
        LocaleFilter::disable();
        $this->translations = $this->translations ?: (new ArrayCollection());

        # Relations like this one are lazy-loaded.
        # At this point, it's likely that even though the base entity is loaded, the related translations are not
        # Calling a method like toArray() on the collection makes sure they are fetched from the database
        # We do this here to make sure that translations are fetched while the locale SQLFilter is disabled
        # Otherwise, we would always end up with a single translation: the one corresponding to the current locale
        $this->translations->toArray();

        if ($reenableFilter) {
            LocaleFilter::enable();
        }

        return $this->translations;
    }

    public function addTranslation($translation) {
        if (!$this->getTranslations()->contains($translation) && $translation != $this) {
            if ($this->getTranslationIn($translation->getLocale())) {
                throw new \Exception(
                    sprintf(
                        "There is already a translation of this locale in this entity. This error occured when adding a %s translation to the entity %s %s.",
                        $translation->getLocale(),
                        get_class($this),
                        $this->getId()
                    )
                );
            }

            $this->getTranslations()->add($translation);
        }

        return $this;
    }

    public function removeTranslation($translation) {
        if ($this->getTranslations()->contains($translation)) {
            $this->getTranslations()->removeElement($translation);
        }

        return $this;
    }

    public function linkToTranslation($translation) {
        $this->addTranslation($translation);
        $translation->addTranslation($this);

        return $this;
    }

    public function getTranslationIn($locale, $createIfMissing = false) {
        return $this->getTranslation($locale);
    }

    public function getTranslation($locale, $createIfMissing = false) {
        if ($this->getLocale() == $locale) {
            return $this;
        }

        foreach ($this->getTranslations() as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation;
            }
        }

        if ($createIfMissing) {
            return $this->translateTo($locale);
        }

        return null;
    }

    public function translateTo($locale) {
        # If an array of locales is provided, return an array of translations
        if (is_array($locale)) {
            $translations = [];

            foreach ($locale as $singleLocale) {
                $translations[$singleLocale] = $this->translateTo($singleLocale);
            }

            return $translations;
        }

        # If the current entity is already in the right locale, return it
        if ($this->getLocale() == $locale) {
            return $this;
        }

        # If the entity is already translated in this locale, return the existing translation
        if ($existingTranslation = $this->getTranslationIn($locale)) {
            return $existingTranslation;
        }

        # Create the new translation entity, based on this one
        $translation = clone $this;
        $translation->setId(null);
        $translation->setLocale($locale);
        $translation->setTranslated(false);

        $this->linkToTranslation($translation);

        return $translation;
    }

    public function archiveTranslations() {
        foreach ($this->getTranslations() as $translation) {
            if (method_exists($translation, 'archive')) {
                $translation->archive();
            }
        }
    }

    public function matchesTranslation($entity) {
        return $this->getTranslations()->contains($entity);
    }
}
