# PAGE - Projeto Integrador DSM Módulo 3

Sistema de Gerenciamento de Planos de Aula e Registro de Aulas  
Faculdade de Itapira - Curso de Desenvolvimento de Sistemas para a Internet (DSM)  
Módulo 3

## Integrantes

- Cauã Araujo Vaz de Lima
- Inácio Custódio Silva
- João Gabriel Marreiro de Souza
- Marco Antônio Bubola
- Rafael Henrique Zanesco

---

## Descrição do Projeto

O **PAGE** é um sistema web desenvolvido como Projeto Integrador do curso de DSM, com o objetivo de facilitar o gerenciamento de planos de aula, disciplinas e o registro de aulas ministradas por professores. O sistema contempla diferentes perfis de usuário, cada um com permissões específicas, promovendo organização, transparência e praticidade no ambiente acadêmico.

---

## Funcionalidades

### 1. Perfis de Usuário e Permissões

#### Coordenador/Admin
- Cria e gerencia disciplinas.
- Cria e gerencia planos de aula (com capítulos e tópicos).
- Visualiza todos os registros de aula dos professores.
- Pode editar planos, capítulos e tópicos.

#### Professor
- Visualiza as disciplinas e planos de aula disponíveis.
- Visualiza capítulos e tópicos dos planos.
- Registra aulas realizadas, marcando quais tópicos ministrou.
- Pode adicionar um tópico extra vinculado ao capítulo e àquela aula específica.
- Visualiza histórico de aulas ministradas.

---

### 2. Planejamento das Telas (Homes)

#### Home do Coordenador/Admin
- Botão para criar disciplina.
- Botão para criar plano de aula (escolhendo disciplina).
- Listagem de planos de aula (com capítulos e tópicos).
- Listagem de professores e seus registros de aula.
- Opção para editar/excluir planos, capítulos e tópicos.

#### Home do Professor
- Lista de disciplinas e planos de aula disponíveis.
- Visualização dos capítulos e tópicos de cada plano.
- Botão para registrar aula:
  - Seleção de disciplina, plano, capítulo e tópicos ministrados.
  - Campo para adicionar tópico extra (opcional, só para aquela aula).
- Histórico de aulas ministradas (com tópicos extras destacados).

---

### 3. Fluxo de Registro de Aula (Professor)

1. Professor acessa a tela "Registrar Aula".
2. Seleciona disciplina e plano de aula.
3. Seleciona capítulo ministrado.
4. Marca os tópicos ministrados (checkbox).
5. Adiciona um tópico extra (opcional).
6. Salva o registro da aula, incluindo o tópico extra (se houver).

---

## Tecnologias Utilizadas

- PHP (backend)
- MySQL (banco de dados)
- HTML, CSS, JavaScript (frontend)
- XAMPP (ambiente de desenvolvimento local)

---

## Estrutura do Projeto

```
/assets
    /img         # Imagens e logo
    /css         # Arquivos de estilo
    /js          # Scripts JavaScript
/config
    conexao.php  # Conexão com o banco de dados
/controllers    # Lógica de autenticação, registro, etc
/views          # Telas do sistema (login, home, registro, etc)
README.md
```

---

## Como rodar o projeto

1. Clone o repositório ou baixe os arquivos.
2. Importe o banco de dados MySQL usando o arquivo SQL fornecido.
3. Configure o arquivo `/config/conexao.php` com os dados do seu banco.
4. Inicie o XAMPP e acesse o sistema via navegador:  
   `http://localhost/Page--PI/`

---

**PAGE** - Projeto Integrador DSM Módulo 3  
Faculdade de Itapira