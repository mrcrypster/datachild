/* Global links handler */
let app = {
  run: function() {
    app.handle_code_hints();
  },


  /* --- Utilities --- */

  // Live event listener
  on: function(qs, type, cb) {
    document.addEventListener(type, function (e) {
      if ( event.target.matches(qs) ) {
        cb.call(event.target, e);
      }
    });
  },


  /* --- Handlers --- */

  // Handle code explanation hints
  code_hints: {},
  handle_code_hints: function() {
    app.on('pre + ul li, pre + ul li *', 'mouseout', function() {
      let code = this.querySelector('code') || this;
      let code_part = code.innerText;

      if ( app.code_hints[code_part] ) {
        app.code_hints[code_part].remove();
        delete app.code_hints[code_part];
        let code_block = code.parentNode.parentNode.previousElementSibling;
        if ( code_block.classList.contains('output') ) {
          code_block = code_block.previousElementSibling;
        }
        let code_content = code_block.querySelector('code').innerHTML.replace(
          '<span class="hint">' + code_part + '</span>',
          code_part
        );
        code_block.querySelector('code').innerHTML = code_content;
      }
    });
    app.on('pre + ul li, pre + ul li *', 'mouseover', function() {
      let code = this.querySelector('code') || this;
      let code_part = code.innerText;
      let code_block = code.parentNode.parentNode.previousElementSibling;
      if ( code_block.classList.contains('output') ) {
        code_block = code_block.previousElementSibling;
      }
      let code_content = code_block.querySelector('code').innerHTML;
      if ( !app.code_hints[code_part] ) {
        code_content = code_content.replace(
          code_part, '<span class="hint">' + code_part + '</span>'
        );
        code_block.querySelector('code').innerHTML = code_content;

        app.code_hints[code_part] = new LeaderLine(
          LeaderLine.pointAnchor(code, {
            x: 1,
            y: 14
          }),
          code_block.querySelector('.hint'),
          {color: '#2ECC40', size: 6, startPlug: 'disc'}
        );
      }
    });
  }
}
