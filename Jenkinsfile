pipeline {
    agent {
        docker { image 'convenia/php-full:latest' }
    }
    environment {
        COMPOSER_NO_INTERACTION = '1'
    }
    stages {
        stage('Install dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }
        stage('Test Ressonance'){
            steps {
                sh  'php artisan test'
            }
        }
        stage('Deploy Resonance') {
            steps {
                withCredentials([sshUserPrivateKey(credentialsId: 'ressonance-private-key', keyFileVariable: 'SSH_KEY')]) {
                    sh '''
                        chmod 600 "$SSH_KEY"
                        eval "$(ssh-agent -s)"
                        ssh-add "$SSH_KEY"
                        ./vendor/bin/envoy run deploy
                    '''
				}
            }
        }
    }
}