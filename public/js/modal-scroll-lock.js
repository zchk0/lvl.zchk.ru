document.addEventListener('livewire:init', () => {
  const html = document.documentElement
  const upd = () => html.classList.toggle('overflow-hidden', !!document.querySelector('[data-modal-open="true"]'))

  upd()
  Livewire.hook('morphed', upd)
  document.addEventListener('livewire:navigated', upd)
})
