<?php
/**
 * Form decorator that renders the label as a hyperlink to the group page
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package Forms
 */
/**
 * Form decorator that renders the label as a hyperlink to the group page
 *
 * How to link the label of element $element to group $groupId:
 * <code>
 * $element->setName('group' . $groupId);
 * $element->addPrefixPath(
 *     'Form_Decorator',
 *      realpath(dirname(__FILE__) . '/Decorator'),
 *      'decorator'
 * );
 * $element->removeDecorator('Label');
 * $element->addDecorator('GroupLabel');
 * </code>
 * @package Forms
 */
class Form_Decorator_GroupLabel extends Zend_Form_Decorator_Label
{

    /**
     * Constructor
     * @param array|Zend_Config $options
     */
    function __construct($options = null)
    {
        parent::__construct($options);

        // Set options that are normally set for Zend_Form_Decorator_Label
        $this->setOption('tag', 'dt');
        $this->setOption('disableFor', true);
    }

    /**
     * Render a label
     * @param string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $groupId = Form_ManageGroupMemberships::extractGroupId($element->getName());
        $view = $element->getView();

        // Preserve values
        $escape = $this->getOption('escape');
        $label = $element->getLabel();

        // Change the label to a hyperlink. This would normally get escaped,
        // so escaping must be turned off and the content gets escaped manually.
        $this->setOption('escape', false);
        $element->setLabel(
            $view->htmlTag(
                'a',
                $view->escape($label),
                array(
                    'href' => $view->standardUrl(
                        array(
                            'controller' => 'group',
                            'action' => 'general',
                            'id' => $groupId,
                        )
                    )
                )
            )
        );

        // Let the parent class do the rendering
        $content = parent::render($content);

        // Restore to previous state
        $this->setOption('escape', $escape);
        $element->setLabel($label);

        return $content;
    }

}
