export function guid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

export function closeDropdown() {  
  document.querySelectorAll('.dropdown-content').forEach(function(el) {
      el.style.display = 'none'
      setTimeout(function() {
          el.style.display = null                                            
      }, 1);                                    
  });
}
