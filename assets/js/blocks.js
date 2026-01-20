(function (wp) {
  'use strict';

  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var __ = wp.i18n.__;
  var useBlockProps = wp.blockEditor.useBlockProps;

  function Placeholder(props) {
    var blockProps = useBlockProps({
      className: 'mt-ticket-block mt-ticket-block--editor',
      style: {
        padding: '16px',
        border: '2px dashed #5b21b6',
        background: 'linear-gradient(135deg,#f5f3ff,#ecfeff)',
        borderRadius: '8px',
        margin: '8px 0',
      },
    });

    return el(
      'div',
      blockProps,
      el('strong', { style: { display: 'block', marginBottom: '8px' } }, props.title),
      el('div', { style: { fontSize: '13px', color: '#666' } }, props.desc)
    );
  }

  // Register blocks directly in JS
  // This ensures blocks appear in the inserter even if PHP registration has issues
  registerBlockType('mt-ticket-bus/seatmap', {
    title: __('MT Ticket Seatmap', 'mt-ticket-bus'),
    description: __('Seat selection block (shows only for ticket products).', 'mt-ticket-bus'),
    icon: 'tickets-alt',
    category: 'widgets',
    edit: function (props) {
      return el(Placeholder, {
        title: 'MT SEATMAP BLOCK',
        desc: __('Плейсхолдър в редактора. На фронтенда ще се показва само при ticket продукт.', 'mt-ticket-bus'),
      });
    },
    save: function () {
      return null; // dynamic block - rendered via PHP render_callback
    },
  });

  registerBlockType('mt-ticket-bus/ticket-summary', {
    title: __('MT Ticket Summary', 'mt-ticket-bus'),
    description: __('Ticket summary block (shows only for ticket products).', 'mt-ticket-bus'),
    icon: 'id-alt',
    category: 'widgets',
    edit: function (props) {
      return el(Placeholder, {
        title: 'MT TICKET SUMMARY BLOCK',
        desc: __('Плейсхолдър в редактора. На фронтенда ще се показва само при ticket продукт.', 'mt-ticket-bus'),
      });
    },
    save: function () {
      return null; // dynamic block - rendered via PHP render_callback
    },
  });
})(window.wp);

