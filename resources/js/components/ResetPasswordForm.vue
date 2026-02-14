<template>
    <div class="card">
        <div class="card-block">
            <form class="form-horizontal" role="form" method="POST" :action="action" novalidate @submit.prevent="submit">
                <div class="auth-header">
                    <h1 class="auth-title">{{ translations.title }}</h1>
                    <p class="auth-subtitle">{{ translations.note }}</p>
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

                    <div class="form-group" :class="{ 'has-danger': errors.password_confirmation }">
                        <label for="password_confirmation">{{ translations.passwordConfirm }}</label>
                        <div class="input-group input-group--custom">
                            <div class="input-group-addon"><i class="input-icon input-icon--lock"></i></div>
                            <input
                                type="password"
                                v-model="form.password_confirmation"
                                class="form-control"
                                :class="{ 'form-control-danger': errors.password_confirmation }"
                                id="password_confirmation"
                                name="password_confirmation"
                                :placeholder="translations.passwordConfirm"
                            >
                        </div>
                        <div v-if="errors.password_confirmation" class="form-control-feedback form-text">
                            {{ errors.password_confirmation[0] }}
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary submit-btn" :disabled="submitting">
                            <i v-if="submitting" class="fa fa-spinner fa-spin"></i>
                            {{ translations.button }}
                        </button>
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
    name: 'ResetPasswordForm',
    props: {
        action: { type: String, required: true },
        translations: { type: Object, default: () => ({}) },
        statusMessage: { type: String, default: '' },
        serverErrors: { type: Array, default: () => [] },
        email: { type: String, default: '' },
        token: { type: String, required: true },
        redirectUrl: { type: String, default: '/admin' },
    },
    setup(props) {
        const form = ref({
            email: props.email,
            password: '',
            password_confirmation: '',
            token: props.token,
        });
        const errors = ref({});
        const submitting = ref(false);

        async function submit() {
            errors.value = {};
            submitting.value = true;
            try {
                const response = await axios.post(props.action, form.value);
                window.location.replace(response.data.redirect || props.redirectUrl);
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
.submit-btn {
    display: block;
    width: 100%;
    padding: 1rem;
    font-size: 16px;
    color: #fff;
}

.submit-btn:hover,
.submit-btn:focus,
.submit-btn:disabled {
    color: #fff;
}

label {
    font-size: .875rem;
}
</style>
