import { nextTick } from 'vue';

/**
 * A failed form leaves keyboard and screen reader users without a clue about what
 * went wrong, so move the focus to the first field marked as invalid.
 */
export async function focusFirstError(): Promise<void> {
    await nextTick();
    document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus();
}
