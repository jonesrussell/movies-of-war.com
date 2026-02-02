<script lang="ts">
import { defineComponent, h } from 'vue';

export default defineComponent({
    name: 'JsonLdScript',
    props: {
        content: { type: String, required: true },
        headKey: { type: String, default: undefined },
    },
    setup(props) {
        return () =>
            h('script', {
                type: 'application/ld+json',
                'head-key': props.headKey,
                innerHTML: props.content,
            });
    },
});
</script>
