<template>
    <div class="app-shell app-dark min-h-screen text-slate-100">
        <RouterView v-slot="{ Component, route }">
            <Transition name="page" mode="out-in">
                <component :is="Component" :key="route.name" />
            </Transition>
        </RouterView>
    </div>
</template>
