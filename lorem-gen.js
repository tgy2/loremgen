(function () {
  if (window.__loremInit) return;
  window.__loremInit = true;

  const typeEl = document.getElementById('lorem-type');
  const countEl = document.getElementById('lorem-count');
  const genBtn = document.getElementById('lorem-gen');
  const copyBtn = document.getElementById('lorem-copy');
  const outputEl = document.getElementById('lorem-output');

  function getCount() {
    // Always at least 1, no 0 allowed
    let n = parseInt(countEl.value, 10);
    if (!n || n < 1) {
      n = 1;
      countEl.value = 1;
    }
    return n;
  }

  function fetchLorem() {
    genBtn.disabled = true;
    genBtn.textContent = 'Generating...';
    fetch(`/wp-json/lorem/v1/generate?type=${typeEl.value}&count=${getCount()}`)
      .then(r =>
        r.headers.get('content-type').includes('application/json')
          ? r.json()
          : r.text()
      )
      .then(data => {
        if (typeEl.value === 'lists' && Array.isArray(data)) {
          outputEl.innerHTML =
            '<ul>' + data.map(item => `<li>${item}</li>`).join('') + '</ul>';
        } else if (Array.isArray(data)) {
          outputEl.innerHTML = data.map(p => `<p>${p}</p>`).join('');
        } else if (typeof data === 'object' && data !== null) {
          outputEl.innerHTML = `<p style="color:red;">${
            data.message || 'Unknown error'
          }</p>`;
        } else {
          outputEl.innerHTML = `<p>${data}</p>`;
        }
      })
      .catch(err => {
        outputEl.innerHTML = `<p style="color:red;">Error: ${err.message}</p>`;
      })
      .finally(() => {
        genBtn.disabled = false;
        genBtn.textContent = 'Generate';
      });
  }

  function copyText(txt) {
    if (navigator.clipboard?.writeText)
      return navigator.clipboard.writeText(txt);
    const ta = document.createElement('textarea');
    ta.value = txt;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    return Promise.resolve();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('lorem-cta-btn');
    if (btn) {
      btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  });

  genBtn.addEventListener('click', fetchLorem);

  copyBtn.addEventListener('click', () => {
    const paras = Array.from(outputEl.querySelectorAll('p')).map(
      p => p.innerText
    );
    copyText(paras.join('\n\n')).then(() => {
      const prev = copyBtn.textContent;
      copyBtn.textContent = 'Copied!';
      setTimeout(() => (copyBtn.textContent = prev), 1000);
    });
  });

  // Initial load with default value only ONCE
  fetchLorem();
})();
