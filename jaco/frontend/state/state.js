export const state = {
    list: [],
    destroy(msg) {
        let index = state.list.findIndex(x => x.id === msg.id)
        state.list.splice(index, 1)
    }
}
