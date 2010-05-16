<?php
class Rotor_Lucene_Model_Index_Document_Product extends Rotor_Lucene_Model_Index_Document_Abstract
{
    const DOCTYPE = 'product';

	protected $_systemAttributes = array('name', 'short_content', 'url');

    protected function getEntityCollection()
    {
        $collection = Mage::getModel('catalog/product')
			->getCollection();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
		$collection->addAttributeToFilter('entity_id', array('gt' => 9700));
		return $collection;
    }

    protected function getEntitySearchModel()
    {
        return Mage::getModel('lucene/index_document_product');
    }

    protected function getDoctype()
    {
        return self::DOCTYPE;
    }

    protected function addAttributes()
    {
        $this->addField(Zend_Search_Lucene_Field::Text('name',
                $this->getSourceModel()->getName(), self::ENCODING));
/*
		$this->addField(Zend_Search_Lucene_Field::UnStored('content', 
			$this->getSourceModel()->getDescription(), self::ENCODING));
        $this->addField(Zend_Search_Lucene_Field::Keyword('category',
            $this->getSourceModel()->getParentCategory()->getName(), self::ENCODING));
*/      
		$this->addField(Zend_Search_Lucene_Field::UnIndexed('short_content', 
			$this->getSourceModel()->getShortDescription(), self::ENCODING));
        $this->addField(Zend_Search_Lucene_Field::UnIndexed('url',
        	$this->getSourceModel()->getUrl(), self::ENCODING));
		foreach($this->getSearchableAttributes() as $attribute) {
			if(in_array($attribute->getAttributeCode(), $this->_systemAttributes)) {
				continue;
			}
			if($this->getSourceModel()->getData($attribute->getAttributeCode())) {
				$value = $this->getSourceModel()->getAttributeText($attribute->getAttributeCode());
				if(!$value) {
					$value = $this->getSourceModel()->getData($attribute->getAttributeCode());
				}
				Mage::log($attribute->getAttributeCode().":".$value);
		        $this->addField(Zend_Search_Lucene_Field::UnStored($attribute->getAttributeCode(),
		        	$value, self::ENCODING));
			}
			
		}
        /*
		if($this->getSourceModel()->getImage()) {
            try {
                $image = Mage::getModel('catalog/product_image')
                ->setBaseFile('../category/'.$this->getSourceModel()->getImage())
                ->setHeight(100)
                ->setWidth(100)
                ->resize()
                ->saveFile()
                ->getUrl();
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('image', $image, self::ENCODING));
            } catch (Exception $e) {
                // no image for category, so none will be added to index
            }
        }
		*/
    }

    protected function getSourceModel()
    {
        if(!isset($this->_entityModel)) {
            $this->_entityModel = Mage::getModel('catalog/product')
				->setStoreId(Mage::app()->getStore()->getId())
				->load($this->_id);
        }
        return $this->_entityModel;
    }

	protected function getSearchableAttributes()
	{
		if(!isset($this->_searchableAttributes)) {
			$this->_searchableAttributes = Mage::getModel('eav/entity_attribute')
				->getCollection()
				->setEntityTypeFilter($this->getSourceModel()->getEntityTypeId());
			$this->_searchableAttributes->getSelect()
				->where('additional_table.is_searchable = ?', 1);
		}
		return $this->_searchableAttributes;
	}
}