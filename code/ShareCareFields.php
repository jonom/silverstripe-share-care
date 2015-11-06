<?php

// Provide default fields and method customisations to complement Open Graph module with minimal setup
class ShareCareFields extends DataExtension {
	
	private static $db = array(
		'OGTitleCustom' => 'Varchar(100)',
		'OGDescriptionCustom' => 'Varchar(150)'
	);

	private static $has_one = array(
		'OGImageCustom' => 'Image',
		'PinterestImageCustom' => 'Image'
	);

	/**
	 * Add CMS fields to allow setting of custom open graph values
	 */
	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab("Root.SocialMedia", TextField::create("OGTitleCustom", 'Share Title')
			->setAttribute('placeholder', $this->owner->getDefaultOGTitle())
			->setDescription('Used for Facebook, Twitter and Pinterest')
			->setMaxLength(90));
		$fields->addFieldToTab("Root.SocialMedia", TextAreaField::create("OGDescriptionCustom", 'Share Description')
			->setDescription('Used for Facebook and Twitter')
			->setAttribute('placeholder', $this->owner->getDefaultOGDescription())
			->setRows(3));
		$fields->addFieldToTab("Root.SocialMedia", UploadField::create('OGImageCustom', 'Share Image')
			->setAllowedFileCategories('image')
			->setAllowedMaxFileNumber(1)
			->setDescription('<a href="https://developers.facebook.com/docs/sharing/best-practices#images" target="_blank">Optimum image ratio</a> is 1.91:1. (1200px wide by 630px tall or better)'));
		if (Config::inst()->get('ShareCare', 'pinterest')) {
			$fields->addFieldToTab("Root.SocialMedia", UploadField::create('PinterestImageCustom', 'Pinterest Image')
				->setAllowedFileCategories('image')
				->setAllowedMaxFileNumber(1)
				->setDescription('Square/portrait or taller images look best on Pinterest. This image should be at least 750px wide.'));
		}
	}
	
	/**
	 * The title that will be used in the 'og:title' open graph tag.
	 * Use a custom value if set, or fallback to a default value.
	 * 
	 * @return string
	 */
	public function getOGTitle() {
		return ($this->owner->OGTitleCustom) ? $this->owner->OGTitleCustom : $this->owner->getDefaultOGTitle();
	}
	
	/**
	 * The default/fallback value to be used in the 'og:title' open graph tag.
	 * 
	 * @return string
	 */
	public function getDefaultOGTitle() {
		return $this->owner->getTitle();
	}

	/**
	 * The description that will be used in the 'og:description' open graph tag.
	 * Use a custom value if set, or fallback to a default value.
	 * 
	 * @return string
	 */
	public function getOGDescription() {
		// Use OG Title if set
		if($this->owner->OGDescriptionCustom) {
			$description = trim($this->owner->OGDescriptionCustom);
			if(!empty($description)) return $description;
		}
		return $this->owner->getDefaultOGDescription();
	}

	/**
	 * The default/fallback value to be used in the 'og:description' open graph tag.
	 * 
	 * @return string
	 */
	public function getDefaultOGDescription() {		
		// Use MetaDescription if set
		if($this->owner->MetaDescription) {
			$description = trim($this->owner->MetaDescription);
			if(!empty($description)) return $description;
		}
		
		// Fall back to Content
		if($this->owner->Content) {
			$description = trim($this->owner->obj('Content')->Summary());
			if(!empty($description)) return $description;
		}
		return false;
	}

  /**
	 * The Image object or absolute URL that will be used for the 'og:image' open graph tag.
	 * Use a custom selection if set, or fallback to a default value.
	 * Image size specs: https://developers.facebook.com/docs/sharing/best-practices#images
	 * 
	 * @return Image|string|false
	 */
	public function getOGImage() {
		$ogImage = $this->owner->OGImageCustom();
		if ($ogImage->exists()) {
			return ($ogImage->getWidth() > 1200) ? $ogImage->setWidth(1200) : $ogImage;
		}
		return $this->owner->getDefaultOGImage();
  }

  /**
	 * The default/fallback Image object or absolute URL to be used in the 'og:image' open graph tag.
	 * Override this method to return a sensible default.
	 * 
	 * @return Image|string|false
	 */
	public function getDefaultOGImage() {
	  // We don't want to use the SilverStripe logo, so let's use a favicon if available.
		return (file_exists(BASE_PATH . '/apple-touch-icon.png'))
			? Director::absoluteURL('apple-touch-icon.png')
			: false;
  }

  /**
	 * Get an Image object to be used in the 'Pin it' ($PinterestShareLink) link.
   * Image size specs: https://developers.pinterest.com/pin_it/
   * 
   * @return Image|null
   */
  public function getPinterestImage() {
		$pinImage = $this->owner->PinterestImageCustom();
		if ($pinImage->exists()) {
			return ($pinImage->getWidth() > 1200) ? $pinImage->setWidth(1200) : $pinImage;
		}
		return $this->owner->getOGImage();
  }
}