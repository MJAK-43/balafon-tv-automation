<template>
    <main class="login-stage">
        <section class="login-story">
            <div class="login-brand">
                <span>B</span>
                <div>
                    <strong>Balafon</strong>
                    <small>Broadcast OS</small>
                </div>
            </div>

            <div class="login-story__copy">
                <p>Régie automatisée / 2026</p>
                <h1 class="display-type">Votre antenne.<br><em>Sous contrôle.</em></h1>
                <span>Planifiez, habillez et diffusez vos programmes depuis un espace de travail conçu pour les opérateurs TV.</span>
            </div>

            <div class="login-signal">
                <span class="signal-dot" />
                <div>
                    <strong>Plateforme opérationnelle</strong>
                    <small>vMix automation ready</small>
                </div>
            </div>
        </section>

        <section class="login-access">
            <form class="login-form" @submit.prevent="submit">
                <div class="login-form__index">01 / AUTH</div>
                <div>
                    <p>Accès opérateur</p>
                    <h2 class="display-type">Ouvrir la régie</h2>
                    <span>Utilisez votre compte Balafon pour accéder à l’espace de diffusion.</span>
                </div>

                <div class="login-fields">
                    <label>
                        <span>Adresse e-mail</span>
                        <input v-model="form.email" autocomplete="email" placeholder="operateur@balafon.tv" type="email">
                    </label>
                    <label>
                        <span>Mot de passe</span>
                        <input v-model="form.password" autocomplete="current-password" placeholder="••••••••" type="password">
                    </label>
                </div>

                <button class="login-submit" type="submit">
                    <span>Entrer dans le studio</span>
                    <ArrowUpRightIcon class="h-5 w-5" />
                </button>

                <p v-if="error" class="login-error">{{ error }}</p>
                <p class="login-hint">Compte de démonstration prérempli pour l’environnement local.</p>
            </form>
        </section>
    </main>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { ArrowUpRightIcon } from '@heroicons/vue/24/outline';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const error = ref('');
const form = reactive({
    email: 'admin@balafon.local',
    password: 'password',
});

const submit = async () => {
    error.value = '';

    try {
        await auth.login(form);
        await router.push({ name: 'dashboard' });
    } catch (err) {
        error.value = err?.response?.data?.message ?? 'Connexion impossible.';
    }
};
</script>

<style scoped>
.login-stage {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    min-height: 100vh;
    padding: 12px;
}
.login-story,
.login-access {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--line);
}
.login-story {
    display: none;
    flex-direction: column;
    justify-content: space-between;
    padding: clamp(34px, 4.2vw, 68px);
    border-radius: 24px 0 0 24px;
    background:
        radial-gradient(circle at 15% 85%, rgba(255, 77, 46, 0.22), transparent 25rem),
        linear-gradient(135deg, rgba(255, 255, 255, 0.03), transparent 45%),
        #111519;
}
.login-story::after {
    position: absolute;
    right: -24%;
    bottom: -26%;
    width: 70%;
    aspect-ratio: 1;
    border: 1px solid rgba(255, 255, 255, 0.055);
    border-radius: 50%;
    content: '';
    box-shadow: 0 0 0 70px rgba(255, 255, 255, 0.018), 0 0 0 140px rgba(255, 255, 255, 0.012);
}
.login-brand {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 12px;
}
.login-brand > span {
    display: grid;
    width: 44px;
    height: 44px;
    place-items: center;
    border-radius: 12px;
    background: var(--signal);
    font-family: 'Bahnschrift', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
}
.login-brand strong,
.login-brand small {
    display: block;
}
.login-brand strong {
    font-family: 'Bahnschrift', sans-serif;
    font-size: 1.05rem;
}
.login-brand small {
    margin-top: 3px;
    color: #656d72;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.login-story__copy {
    position: relative;
    z-index: 1;
    max-width: 700px;
}
.login-story__copy p {
    color: var(--signal-bright);
    font-size: 0.65rem;
    font-weight: 720;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}
.login-story__copy h1 {
    margin: 22px 0 0;
    font-size: clamp(3.4rem, 5.1vw, 6.5rem);
    font-weight: 610;
    letter-spacing: -0.07em;
    line-height: 0.88;
}
.login-story__copy h1 em {
    color: var(--signal);
    font-style: normal;
}
.login-story__copy > span {
    display: block;
    max-width: 590px;
    margin-top: 28px;
    color: #959da1;
    font-size: 0.92rem;
    line-height: 1.75;
}
.login-signal {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 14px;
}
.login-signal strong,
.login-signal small {
    display: block;
}
.login-signal strong {
    font-size: 0.72rem;
}
.login-signal small {
    margin-top: 3px;
    color: #626a6f;
    font-size: 0.6rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.login-access {
    display: grid;
    min-width: 0;
    min-height: calc(100vh - 24px);
    place-items: center;
    padding: clamp(30px, 4vw, 68px);
    border-radius: 24px;
    background: rgba(8, 10, 12, 0.68);
}
.login-form {
    width: 100%;
    max-width: 500px;
    min-width: 0;
}
.login-form__index {
    margin-bottom: clamp(48px, 7vh, 76px);
    color: #51595e;
    font-family: 'Bahnschrift', sans-serif;
    font-size: 0.62rem;
    letter-spacing: 0.18em;
}
.login-form > div:nth-child(2) > p {
    margin: 0;
    color: var(--signal-bright);
    font-size: 0.63rem;
    font-weight: 720;
    letter-spacing: 0.21em;
    text-transform: uppercase;
}
.login-form h2 {
    margin: 12px 0 0;
    font-size: clamp(2.45rem, 3.8vw, 4.25rem);
    font-weight: 610;
    letter-spacing: -0.055em;
}
.login-form > div:nth-child(2) > span {
    display: block;
    max-width: 430px;
    margin-top: 14px;
    color: #7d858a;
    font-size: 0.78rem;
    line-height: 1.65;
}
.login-fields {
    display: grid;
    gap: 19px;
    margin-top: 36px;
}
.login-fields label > span {
    display: block;
    margin-bottom: 8px;
    color: #777f84;
    font-size: 0.66rem;
    font-weight: 620;
}
.login-fields input {
    width: 100%;
    max-width: 100%;
    min-height: 52px;
    padding: 0 16px;
    border-radius: 13px;
}
.login-submit {
    display: flex;
    width: 100%;
    max-width: 100%;
    min-height: 54px;
    align-items: center;
    justify-content: space-between;
    margin-top: 22px;
    padding: 0 18px;
    border: 0;
    border-radius: 13px;
    background: var(--signal);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 18px 42px rgba(255, 77, 46, 0.23);
}
.login-submit:hover {
    background: var(--signal-bright);
}
.login-error {
    margin: 16px 0 0;
    color: #ff8799;
    font-size: 0.72rem;
}
.login-hint {
    margin: 22px 0 0;
    color: #525a5f;
    font-size: 0.62rem;
    text-align: center;
}
@media (max-width: 959px) {
    .login-stage {
        display: block;
        width: 100%;
        max-width: 100vw;
        overflow: hidden;
    }
    .login-access {
        width: 100%;
        max-width: 100%;
    }
    .login-form,
    .login-fields,
    .login-fields label {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .login-form > div:nth-child(2) > span,
    .login-hint {
        overflow-wrap: anywhere;
    }
}
@media (min-width: 960px) {
    .login-stage {
        grid-template-columns: minmax(0, 1.15fr) minmax(480px, 0.85fr);
    }
    .login-story {
        display: flex;
    }
    .login-access {
        border-left: 0;
        border-radius: 0 24px 24px 0;
    }
}
</style>
