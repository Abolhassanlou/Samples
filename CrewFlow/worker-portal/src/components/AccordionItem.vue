<script setup>
import { ref } from 'vue'

defineProps({
  label: { type: String, required: true },
  hint: { type: String, default: '' },
  nested: { type: Boolean, default: false }, // slightly smaller styling for sub-items
})

const open = ref(false)
</script>

<template>
  <div class="accordion-item" :class="{ 'accordion-item--nested': nested }">
    <button class="accordion-header" @click="open = !open">
      <span class="accordion-header-text">
        <span class="accordion-label">{{ label }}</span>
        <span v-if="hint" class="accordion-hint">{{ hint }}</span>
      </span>
      <span class="accordion-toggle" :class="{ 'accordion-toggle--open': open }">+</span>
    </button>

    <div v-if="open" class="accordion-body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.accordion-item {
  border-bottom: 1px solid var(--color-line);
}

.accordion-item:last-child {
  border-bottom: none;
}

.accordion-item--nested {
  border-bottom: 1px solid var(--color-line);
  background: var(--color-paper);
}

.accordion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.85rem 1rem;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
}

.accordion-header-text {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.accordion-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-ink);
}

.accordion-item--nested .accordion-label {
  font-size: 0.85rem;
}

.accordion-hint {
  font-size: 0.78rem;
  color: var(--color-slate);
}

.accordion-toggle {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-amber-dark);
  background: rgba(224, 151, 58, 0.14);
  border-radius: 50%;
  transition: transform 0.15s ease;
}

.accordion-toggle--open {
  transform: rotate(45deg);
}

.accordion-body {
  padding: 0 1rem 1rem;
}
</style>
