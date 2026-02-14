<template>
    <div class="card">
        <div class="card-block">
            <form class="form-horizontal" role="form" method="POST" :action="action" novalidate @submit.prevent="submit">
                <div class="auth-header">
                    <h1 class="auth-title">{{ translations.title }}</h1>
                    <p class="auth-subtitle">{{ translations.signInText }}</p>
                </div>
                <div class="auth-body">
                    <div v-if="statusMessage" class="alert alert-success">
                        {{ statusMessage }}
                    </div>
                    <div v-if="serverErrors.length" class="alert alert-danger">
                        <ul>
                            <li v-for="(error, index) in serverErrors" :key="index">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="form-group" :class="{ 'has-danger': errors.email }">
                        <label for="email">{{ translations.email }}</label>
                        <div class="input-group input-group--custom">
                            <div class="input-group-addon"><i class="input-icon input-icon--mail"></i></div>
                            <input
                                type="text"
                                v-model="form.email"
                                class="form-control"
                                :class="{ 'form-control-danger': errors.email }"
                                id="email"
                                name="email"
                                :placeholder="translations.email"
                            >
                        </div>
                        <div v-if="errors.email" class="form-control-feedback form-text">
                            {{ errors.email[0] }}
                        </div>
                    </div>

                    <div class="form-group" :class="{ 'has-danger': errors.password }">
                        <label for="password">{{ translations.password }}</label>
                        <div class="input-group input-group--custom">
                            <div class="input-group-addon"><i class="input-icon input-icon--lock"></i></div>
                            <input
                                type="password"
                                v-model="form.password"
                                class="form-control"
                                :class="{ 'form-control-danger': errors.password }"
                                id="password"
                                name="password"
                                :placeholder="translations.password"
                            >
                        </div>
                        <div v-if="errors.password" class="form-control-feedback form-text">
                            {{ errors.password[0] }}
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="hidden" name="remember" value="1">
                        <button type="submit" class="btn btn-primary login-btn" :disabled="submitting">
                            <i v-if="submitting" class="fa fa-spinner fa-spin"></i>
                            {{ translations.button }}
                        </button>
                    </div>
                    <div class="form-group text-center">
                        <a :href="passwordResetUrl" class="auth-ghost-link forgot-link">{{ translations.forgotPassword }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';

export default {
    name: 'LoginForm',
    props: {
        action: { type: String, required: true },
        redirectUrl: { type: String, default: '/admin' },
        translations: { type: Object, default: () => ({}) },
        passwordResetUrl: { type: String, default: '/admin/password-reset' },
        statusMessage: { type: String, default: '' },
        serverErrors: { type: Array, default: () => [] },
    },
    setup(props) {
        const form = ref({ email: '', password: '' });
        const errors = ref({});
        const submitting = ref(false);

        async function submit() {
            errors.value = {};
            submitting.value = true;
            try {
                await axios.post(props.action, form.value);
                window.location.replace(props.redirectUrl);
            } catch (e) {
                if (e.response && e.response.status === 422) {
                    errors.value = e.response.data.errors || {};
                }
                submitting.value = false;
            }
        }

        return { form, errors, submitting, submit };
    },
};
</script>

<style scoped>
.login-btn {
    display: block;
    width: 100%;
    padding: 1rem;
    font-size: 16px;
    color: #fff;
}

.login-btn:hover,
.login-btn:focus,
.login-btn:disabled {
    color: #fff;
}

label {
    font-size: .875rem;
}

.forgot-link {
    text-decoration: none;
}

.forgot-link:hover {
    text-decoration: none;
}
</style>
