<script lang="ts">
import { defineComponent, h } from 'vue';

export default defineComponent({
    name: 'JsonLdScript',
    props: {
        content: { type: String, required: true },
    },
    setup(props) {
        return () =>
            h('script', {
                type: 'application/ld+json',
                innerHTML: props.content,
            });
    },
});
</script>
