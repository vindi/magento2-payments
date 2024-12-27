<?php
namespace Vindi\VP\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;

class EmailTemplate implements OptionSourceInterface
{
    /**
     * @var TemplateCollectionFactory
     */
    private $templateCollectionFactory;

    /**
     * EmailTemplate constructor.
     * @param TemplateCollectionFactory $templateCollectionFactory
     */
    public function __construct(
        TemplateCollectionFactory $templateCollectionFactory
    ) {
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * Get available email templates including custom templates based on 'payment_link_template'
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $collection = $this->templateCollectionFactory->create();
        $collection->load();

        $options[] = [
            'value' => 'payment_link_template',
            'label' => __('Payment Link Notification (VP) - [Default]'),
        ];

        foreach ($collection as $template) {
            if ($template->getOrigTemplateCode() == 'payment_link_template') {
                $options[] = [
                    'value' => $template->getTemplateId(),
                    'label' => $template->getTemplateCode(),
                ];
            }
        }

        return $options;
    }
}
