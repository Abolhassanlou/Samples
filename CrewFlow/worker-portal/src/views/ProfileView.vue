<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import AccordionItem from '@/components/AccordionItem.vue'
import CustomFieldSection from '@/components/CustomFieldSection.vue'
import DocumentUploadSection from '@/components/DocumentUploadSection.vue'
import ContractsSection from '@/components/ContractsSection.vue'
import PersonalDetailsForm from '@/components/PersonalDetailsForm.vue'
import AddressForm from '@/components/AddressForm.vue'
import BankDetailsForm from '@/components/BankDetailsForm.vue'

const auth = useAuthStore()
const router = useRouter()

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <AppShell>
    <header class="header">
      <h1 class="title">Profile</h1>
    </header>

    <div class="id-card">
      <div class="id-card-main">
        <div class="avatar">{{ auth.user?.name?.charAt(0) }}</div>
        <div class="id-info">
          <span class="id-name">{{ auth.user?.name }}</span>
          <span class="id-company">{{ auth.companyCode }}</span>
        </div>
      </div>
      <button class="logout-button" @click="handleLogout">Sign out</button>
    </div>

    <div class="accordion-group">
      <AccordionItem label="My info" hint="Personal details, address, bank, skills, personal documents">
        <div class="nested-group">
          <AccordionItem label="Personal details" nested>
            <PersonalDetailsForm />
            <div class="custom-questions">
              <h4 class="custom-questions-title">Additional questions from your company</h4>
              <CustomFieldSection category="personal_info" />
            </div>
          </AccordionItem>
          <AccordionItem label="Address" nested>
            <AddressForm />
          </AccordionItem>
          <AccordionItem label="Bank details" nested>
            <BankDetailsForm />
          </AccordionItem>
          <AccordionItem label="Skills" hint="Size, car, license, experience, and more" nested>
            <CustomFieldSection category="skill" />
          </AccordionItem>
          <AccordionItem label="Personal documents" hint="Photo, passport, ID, bank card, and more" nested>
            <DocumentUploadSection category="personal" />
          </AccordionItem>
        </div>
      </AccordionItem>

      <AccordionItem label="Accounting" hint="Hours worked this month, per shift/event">
        <p class="coming-soon">Coming soon — needs shift/assignment data wired in.</p>
      </AccordionItem>

      <AccordionItem label="Payroll" hint="Pay calculations for hours worked">
        <p class="coming-soon">
          Coming soon. Priority: event-based pay (the hourly rate set on that event × hours
          worked). Daily-wage entry for full-time/part-time workers comes after, refined
          later.
        </p>
      </AccordionItem>

      <AccordionItem label="Documents" hint="Work contracts, and job-related uploads">
        <div class="nested-group">
          <AccordionItem label="Work contracts" nested>
            <ContractsSection />
          </AccordionItem>

          <AccordionItem label="My uploads" hint="Job/event-related documents" nested>
            <DocumentUploadSection category="work" />
          </AccordionItem>
        </div>
      </AccordionItem>

      <AccordionItem label="Share app" hint="Your referral code">
        <p class="coming-soon">Coming soon.</p>
      </AccordionItem>

      <AccordionItem label="Settings">
        <div class="nested-group">
          <AccordionItem label="Language" nested>
            <p class="coming-soon">Coming soon.</p>
          </AccordionItem>
          <AccordionItem label="Company" nested>
            <p class="coming-soon">{{ auth.companyCode }} — switching companies coming soon.</p>
          </AccordionItem>
          <AccordionItem label="Delete account" nested>
            <p class="coming-soon">Coming soon.</p>
          </AccordionItem>
        </div>
      </AccordionItem>
    </div>
  </AppShell>
</template>

<style scoped>
.header {
  padding: 1.25rem 1.25rem 0.5rem;
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.3rem;
  margin: 0;
  color: var(--color-ink);
}

.id-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin: 0.75rem 1.25rem 1.25rem;
  padding: 1rem;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 12px;
}

.id-card-main {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  min-width: 0;
}

.avatar {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: var(--color-ink);
  color: var(--color-paper);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.id-info {
  display: flex;
  flex-direction: column;
}

.id-name {
  font-weight: 700;
  font-size: 0.95rem;
}

.id-company {
  font-family: var(--font-mono);
  font-size: 0.78rem;
  color: var(--color-slate);
}

.accordion-group {
  margin: 0 1.25rem 1.5rem;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 12px;
  overflow: hidden;
}

.nested-group {
  border: 1px solid var(--color-line);
  border-radius: 8px;
  overflow: hidden;
}

.coming-soon {
  font-size: 0.82rem;
  color: var(--color-slate);
  line-height: 1.5;
  margin: 0;
}

.custom-questions {
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px dashed var(--color-line);
}

.custom-questions-title {
  font-family: var(--font-display);
  font-size: 0.85rem;
  color: var(--color-slate);
  margin: 0 0 0.9rem;
}

.logout-button {
  flex-shrink: 0;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.45rem 0.85rem;
  color: var(--color-danger);
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}
</style>
